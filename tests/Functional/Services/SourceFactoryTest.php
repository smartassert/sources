<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Repository\SourceRepository;
use App\Request\CreateSourceRequest;
use App\Services\SourceFactory;
use Doctrine\ORM\EntityManagerInterface;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class SourceFactoryTest extends WebTestCase
{
    private const USER_ID = '01FPSVJ7ZT85X73BW05EK9B3XG';

    private SourceFactory $factory;
    private EntityManagerInterface $entityManager;
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(SourceFactory::class);
        \assert($factory instanceof SourceFactory);
        $this->factory = $factory;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $this->removeAllSources();
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
     * @dataProvider createFromRequestDataProvider
     */
    public function testCreateFromRequest(UserInterface $user, CreateSourceRequest $request): void
    {
        $source = $this->factory->createFromRequest($user, $request);

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
    public function createFromRequestDataProvider(): array
    {
        $user = new User(self::USER_ID);

        return [
            'empty access token' => [
                'user' => $user,
                'request' => new CreateSourceRequest(
                    'https://example.com/repository.git',
                    '/',
                    null
                ),
            ],
            'non-empty access token' => [
                'user' => $user,
                'request' => new CreateSourceRequest(
                    'https://example.com/repository.git',
                    '/',
                    'access-token',
                ),
            ],
        ];
    }

    public function testCreateWhenSourceAlreadyExists(): void
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

    private function assertCreatedSource(AbstractSource $source, string $expectedUserId): void
    {
        self::assertTrue(Ulid::isValid($source->getId()));
        self::assertSame($expectedUserId, $source->getUserId());
    }

    private function removeAllSources(): void
    {
        $sources = $this->repository->findAll();

        foreach ($sources as $source) {
            $this->entityManager->remove($source);
        }

        $this->entityManager->flush();
    }
}
