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
use App\Services\Source\Factory;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @dataProvider createFromGitSourceRequestDataProvider
     */
    public function testCreateFromGitSourceRequest(User $user, GitSourceRequest $request, GitSource $expected): void
    {
        self::assertCount(0, $this->repository->findAll());

        $source = $this->factory->createFromGitSourceRequest($user, $request);
        self::assertInstanceOf(SourceInterface::class, $source);

        self::assertCount(1, $this->repository->findAll());
        $this->factory->createFromGitSourceRequest($user, $request);
        $this->factory->createFromGitSourceRequest($user, $request);
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
    public function createFromGitSourceRequestDataProvider(): array
    {
        $userId = UserId::create();
        \assert('' !== $userId);
        $user = new User($userId, 'non-empty string');
        $label = 'git source label';
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';

        return [
            'git, empty credentials' => [
                'user' => $user,
                'request' => new GitSourceRequest($label, $hostUrl, $path, ''),
                'expected' => new GitSource($userId, $label, $hostUrl, $path, ''),
            ],
            'git, non-empty credentials' => [
                'user' => $user,
                'request' => new GitSourceRequest($label, $hostUrl, $path, 'credentials'),
                'expected' => new GitSource($userId, $label, $hostUrl, $path, 'credentials'),
            ],
        ];
    }

    /**
     * @dataProvider createFromFileSourceRequestDataProvider
     */
    public function testCreateFromFileSourceRequest(User $user, FileSourceRequest $request, FileSource $expected): void
    {
        self::assertCount(0, $this->repository->findAll());

        $source = $this->factory->createFromFileSourceRequest($user, $request);
        self::assertInstanceOf(SourceInterface::class, $source);

        self::assertCount(1, $this->repository->findAll());
        $this->factory->createFromFileSourceRequest($user, $request);
        $this->factory->createFromFileSourceRequest($user, $request);
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
    public function createFromFileSourceRequestDataProvider(): array
    {
        $userId = UserId::create();
        \assert('' !== $userId);
        $user = new User($userId, 'non-empty string');

        return [
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
