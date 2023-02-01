<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Services\Source\Factory;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use webignition\ObjectReflector\ObjectReflector;

class FactoryTest extends WebTestCase
{
    private Factory $factory;
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(Factory::class);
        \assert($factory instanceof Factory);
        $this->factory = $factory;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testCreateFromInvalidSourceRequest(): void
    {
        self::assertNull($this->factory->createFromSourceRequest(
            \Mockery::mock(UserInterface::class),
            new InvalidSourceTypeRequest('invalid')
        ));
    }

    /**
     * @dataProvider createFromSourceRequestDataProvider
     */
    public function testCreateFromSourceRequest(
        UserInterface $user,
        FileSourceRequest|GitSourceRequest $request,
        SourceInterface $expected
    ): void {
        self::assertCount(0, $this->repository->findAll());

        $source = $this->factory->createFromSourceRequest($user, $request);
        self::assertInstanceOf(SourceInterface::class, $source);

        self::assertCount(1, $this->repository->findAll());
        $this->factory->createFromSourceRequest($user, $request);
        $this->factory->createFromSourceRequest($user, $request);
        self::assertCount(1, $this->repository->findAll());

        ObjectReflector::setProperty(
            $expected,
            AbstractSource::class,
            'id',
            $source->getId()
        );

        self::assertEquals($expected, $source);
    }

    /**
     * @return array<mixed>
     */
    public function createFromSourceRequestDataProvider(): array
    {
        $userId = UserId::create();
        \assert('' !== $userId);
        $user = new User($userId, 'non-empty string');
        $gitSourceHostUrl = 'https://example.com/repository.git';
        $gitSourcePath = '/';

        return [
            'git, empty credentials' => [
                'user' => $user,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $gitSourceHostUrl,
                        GitSourceRequest::PARAMETER_PATH => $gitSourcePath,
                        GitSourceRequest::PARAMETER_CREDENTIALS => '',
                    ]
                )),
                'expected' => new GitSource($userId, $gitSourceHostUrl, $gitSourcePath, ''),
            ],
            'git, non-empty credentials' => [
                'user' => $user,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $gitSourceHostUrl,
                        GitSourceRequest::PARAMETER_PATH => $gitSourcePath,
                        GitSourceRequest::PARAMETER_CREDENTIALS => 'credentials',
                    ]
                )),
                'expected' => new GitSource($userId, $gitSourceHostUrl, $gitSourcePath, 'credentials'),
            ],
            'file' => [
                'user' => $user,
                'request' => new FileSourceRequest(new Request(
                    request: [
                        FileSourceRequest::PARAMETER_LABEL => 'file source label',
                    ]
                )),
                'expected' => new FileSource($userId, 'file source label'),
            ],
        ];
    }
}
