<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Source;
use App\Repository\SourceRepository;
use App\Request\CreateSourceRequest;
use App\Services\SourceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
     * @dataProvider createDataProvider
     */
    public function testCreate(
        string $userId,
        string $hostUrl,
        string $path,
        ?string $accessToken
    ): void {
        $source = $this->factory->create($userId, $hostUrl, $path, $accessToken);

        $this->assertCreatedSource($source, $userId, $hostUrl, $path, $accessToken);
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
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
     * @dataProvider createFromRequestDataProvider
     */
    public function testCreateFromRequest(CreateSourceRequest $request): void
    {
        $source = $this->factory->createFromRequest($request);

        $this->assertCreatedSource(
            $source,
            $request->getUserId(),
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
        return [
            'empty access token' => [
                'request' => new CreateSourceRequest(
                    self::USER_ID,
                    'https://example.com/repository.git',
                    '/',
                    null
                ),
            ],
            'non-empty access token' => [
                'request' => new CreateSourceRequest(
                    self::USER_ID,
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

        $source = $this->factory->create($userId, $hostUrl, $path, null);
        self::assertCount(1, $this->repository->findAll());

        $accessTokenVariants = [null, 'access token one', 'access token two'];

        foreach ($accessTokenVariants as $accessTokenVariant) {
            self::assertSame(
                $source,
                $this->factory->create($userId, $hostUrl, $path, $accessTokenVariant)
            );
        }

        self::assertCount(1, $this->repository->findAll());
    }

    private function assertCreatedSource(
        Source $source,
        string $expectedUserId,
        string $expectedHostUrl,
        string $expectedPath,
        ?string $expectedAccessToken
    ): void {
        self::assertTrue(Ulid::isValid($source->getId()));
        self::assertSame($expectedUserId, $source->getUserId());
        self::assertSame($expectedHostUrl, $source->getHostUrl());
        self::assertSame($expectedPath, $source->getPath());
        self::assertSame($expectedAccessToken, $source->getAccessToken());

        $retrievedSource = $this->repository->find($source->getId());
        self::assertSame($source, $retrievedSource);
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
