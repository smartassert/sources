<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Services\SourceFactory;
use App\Tests\Services\SourceRemover;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class SourceFactoryTest extends WebTestCase
{
    private const USER_ID = '01FPSVJ7ZT85X73BW05EK9B3XG';

    private SourceFactory $factory;
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(SourceFactory::class);
        \assert($factory instanceof SourceFactory);
        $this->factory = $factory;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider createGitSourceDataProvider
     */
    public function testCreateGitSource(string $userId, string $hostUrl, string $path, ?string $accessToken): void
    {
        $source = $this->factory->createGitSource($userId, $hostUrl, $path, $accessToken);

        $this->assertCreatedGitSource($source, $userId, $hostUrl, $path, $accessToken);
    }

    /**
     * @return array<mixed>
     */
    public function createGitSourceDataProvider(): array
    {
        return [
            'empty access token' => [
                'userId' => self::USER_ID,
                'hostUrl' => 'https://example.com/repository.git',
                'path' => '/',
                'accessToken ' => null,
            ],
            'non-empty access token' => [
                'userId' => self::USER_ID,
                'hostUrl' => 'https://example.com/repository.git',
                'path' => '/',
                'accessToken ' => 'access-token',
            ],
        ];
    }

    /**
     * @dataProvider createFileSourceDataProvider
     */
    public function testCreateFileSource(string $userId, string $label): void
    {
        $source = $this->factory->createFileSource($userId, $label);

        $this->assertCreatedFileSource($source, $userId, $label);
    }

    /**
     * @return array<mixed>
     */
    public function createFileSourceDataProvider(): array
    {
        return [
            'default' => [
                'userId' => self::USER_ID,
                'label' => 'source label',
            ],
        ];
    }

    /**
     * @dataProvider createFileSourceDataProvider
     */
    public function testCreateRunSource(string $userId): void
    {
        $parent = $this->factory->createFileSource($userId, 'file source label');
        $parameters = ['ref' => 'v0.1'];

        $source = $this->factory->createRunSource($parent, $parameters);

        $this->assertCreatedRunSource($source, $userId, $parent, $parameters);
    }

    /**
     * @return array<mixed>
     */
    public function createRunSourceDataProvider(): array
    {
        return [
            'default' => [
                'userId' => self::USER_ID,
            ],
        ];
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
        $user = new User(self::USER_ID);
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

    public function testCreateGitSourceAlreadyExists(): void
    {
        self::assertCount(0, $this->repository->findAll());

        $userId = self::USER_ID;
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';

        $source = $this->factory->createGitSource($userId, $hostUrl, $path, null);
        self::assertCount(1, $this->repository->findAll());

        $accessTokenVariants = [null, 'access token one', 'access token two'];

        foreach ($accessTokenVariants as $accessTokenVariant) {
            self::assertSame(
                $source,
                $this->factory->createGitSource($userId, $hostUrl, $path, $accessTokenVariant)
            );
        }

        self::assertCount(1, $this->repository->findAll());
    }

    public function testCreateFileSourceAlreadyExists(): void
    {
        self::assertCount(0, $this->repository->findAll());

        $userId = self::USER_ID;
        $label = 'https://example.com/repository.git';

        $this->factory->createFileSource($userId, $label);
        $this->factory->createFileSource($userId, $label);
        self::assertCount(1, $this->repository->findAll());
    }

    public function testCreateRunSourceAlreadyExists(): void
    {
        $userId = self::USER_ID;

        self::assertCount(0, $this->repository->findAll());

        $parent = $this->factory->createFileSource($userId, 'file source label');

        self::assertCount(1, $this->repository->findAll());

        $parameters = ['ref' => 'v0.1'];

        $this->factory->createRunSource($parent, $parameters);
        $this->factory->createRunSource($parent, $parameters);
        self::assertCount(2, $this->repository->findAll());
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
        self::assertSame($expectedAccessToken, $source->getAccessToken());
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
