<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Factory;
use App\Tests\Model\UserId;
use App\Tests\Services\Source\SourceRemover;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

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

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
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

    public function testCreateRunSource(): void
    {
        $user = new User(UserId::create());
        $parent = new FileSource($user->getUserIdentifier(), 'file source label');
        $parameters = ['ref' => 'v0.1'];

        $source = $this->factory->createRunSource($parent, $parameters);

        $this->assertCreatedRunSource($source, $user->getUserIdentifier(), $parent, $parameters);
        self::assertCount(0, $this->repository->findAll());
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
            $request->getAccessToken()
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
            'empty access token' => [
                'user' => $user,
                'request' => new GitSourceRequest($hostUrl, $path, null),
            ],
            'non-empty access token' => [
                'user' => $user,
                'request' => new GitSourceRequest($hostUrl, $path, 'access-token'),
            ],
        ];
    }

    public function testCreateGitSourceFromRequestIsIdempotent(): void
    {
        self::assertCount(0, $this->repository->findAll());

        $user = new User(UserId::create());
        $request = new GitSourceRequest('https://example.com/repository.git', '/', null);

        $this->factory->createGitSourceFromRequest($user, $request);
        $this->factory->createGitSourceFromRequest($user, $request);
        $this->factory->createGitSourceFromRequest($user, $request);

        self::assertCount(1, $this->repository->findAll());
    }

    private function assertCreatedGitSource(
        GitSource $source,
        string $expectedUserId,
        string $expectedHostUrl,
        string $expectedPath,
        ?string $expectedAccessToken
    ): void {
        $this->assertCreatedSource($source, $expectedUserId);

        self::assertSame($expectedHostUrl, $source->getHostUrl());
        self::assertSame($expectedPath, $source->getPath());
        self::assertSame($expectedAccessToken, $source->getCredentials());
    }

    private function assertCreatedFileSource(
        FileSource $source,
        string $expectedUserId,
        string $expectedLabel
    ): void {
        $this->assertCreatedSource($source, $expectedUserId);

        self::assertSame($expectedLabel, $source->getLabel());
    }

    /**
     * @param array<string, string> $expectedParameters
     */
    private function assertCreatedRunSource(
        RunSource $source,
        string $expectedUserId,
        FileSource|GitSource $expectedParent,
        array $expectedParameters
    ): void {
        $this->assertCreatedSource($source, $expectedUserId);

        self::assertEquals($expectedParent, $source->getParent());
        self::assertEquals($expectedParameters, $source->getParameters());
    }

    private function assertCreatedSource(SourceInterface $source, string $expectedUserId): void
    {
        self::assertTrue(Ulid::isValid($source->getId()));
        self::assertSame($expectedUserId, $source->getUserId());
    }
}
