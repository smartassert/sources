<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\FooFileSourceRequest;
use App\Request\FooGitSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Factory;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;
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

    public function testCreateFileSourceFromRequest(): void
    {
        $user = new User(UserId::create());
        $request = new FileSourceRequest('file source label');
        $source = $this->factory->createFileSourceFromRequest($user, $request);

        $this->assertCreatedFileSource($source, $user->getUserIdentifier(), $request->getLabel());
    }

    public function testCreateFileSourceFromRequestIsIdempotent(): void
    {
        self::assertCount(0, $this->repository->findAll());

        $user = new User(UserId::create());
        $request = new FileSourceRequest('file source label');

        $this->factory->createFileSourceFromRequest($user, $request);
        $this->factory->createFileSourceFromRequest($user, $request);
        $this->factory->createFileSourceFromRequest($user, $request);

        self::assertCount(1, $this->repository->findAll());
    }

    /**
     * @dataProvider createGitSourceFromRequestDataProvider
     */
    public function testCreateGitSourceFromRequest(UserInterface $user, GitSourceRequest $request): void
    {
        $source = $this->factory->createGitSourceFromRequest($user, $request);

        $this->assertCreatedGitSource(
            $source,
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getCredentials()
        );
    }

    /**
     * @return array<mixed>
     */
    public function createGitSourceFromRequestDataProvider(): array
    {
        $user = new User(UserId::create());
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';

        return [
            'empty credentials' => [
                'user' => $user,
                'request' => new GitSourceRequest($hostUrl, $path, ''),
            ],
            'non-empty credentials' => [
                'user' => $user,
                'request' => new GitSourceRequest($hostUrl, $path, 'credentials'),
            ],
        ];
    }

    public function testCreateGitSourceFromRequestIsIdempotent(): void
    {
        self::assertCount(0, $this->repository->findAll());

        $user = new User(UserId::create());
        $request = new GitSourceRequest('https://example.com/repository.git', '/', '');

        $this->factory->createGitSourceFromRequest($user, $request);
        $this->factory->createGitSourceFromRequest($user, $request);
        $this->factory->createGitSourceFromRequest($user, $request);

        self::assertCount(1, $this->repository->findAll());
    }

    /**
     * @dataProvider createFromSourceRequestDataProvider
     */
    public function testCreateFromSourceRequest(
        UserInterface $user,
        FooFileSourceRequest|FooGitSourceRequest $request,
        SourceInterface $expected
    ): void {
        $source = $this->factory->createFromSourceRequest($user, $request);

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
        $user = new User($userId);
        $gitSourceHostUrl = 'https://example.com/repository.git';
        $gitSourcePath = '/';

        return [
            'git, empty credentials' => [
                'user' => $user,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $gitSourceHostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $gitSourcePath,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => '',
                ]),
                'expected' => new GitSource($userId, $gitSourceHostUrl, $gitSourcePath, ''),
            ],
            'git, non-empty credentials' => [
                'user' => $user,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $gitSourceHostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $gitSourcePath,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => 'credentials',
                ]),
                'expected' => new GitSource($userId, $gitSourceHostUrl, $gitSourcePath, 'credentials'),
            ],
            'file' => [
                'user' => $user,
                'request' => new FooFileSourceRequest([
                    FooFileSourceRequest::PARAMETER_LABEL => 'file source label',
                ]),
                'expected' => new FileSource($userId, 'file source label'),
            ],
        ];
    }

    private function assertCreatedGitSource(
        GitSource $source,
        string $expectedUserId,
        string $expectedHostUrl,
        string $expectedPath,
        ?string $expectedCredentials
    ): void {
        $this->assertCreatedSource($source, $expectedUserId);

        self::assertSame($expectedHostUrl, $source->getHostUrl());
        self::assertSame($expectedPath, $source->getPath());
        self::assertSame($expectedCredentials, $source->getCredentials());
    }

    private function assertCreatedFileSource(
        FileSource $source,
        string $expectedUserId,
        string $expectedLabel
    ): void {
        $this->assertCreatedSource($source, $expectedUserId);

        self::assertSame($expectedLabel, $source->getLabel());
    }

    private function assertCreatedSource(SourceInterface $source, string $expectedUserId): void
    {
        self::assertTrue(Ulid::isValid($source->getId()));
        self::assertSame($expectedUserId, $source->getUserId());
    }
}
