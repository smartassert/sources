<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Tests\DataProvider\GetSourceDataProviderTrait;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceOriginFactory;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;

abstract class AbstractDeleteSourceTest extends AbstractApplicationTest
{
    use GetSourceDataProviderTrait;

    private SourceRepository $sourceRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider getSourceDataProvider
     *
     * @param callable(AuthenticationConfiguration $authenticationConfiguration): SourceInterface $sourceCreator
     * @param callable(SourceInterface $source): array<mixed> $expectedResponseDataCreator
     */
    public function testDeleteSuccess(callable $sourceCreator, callable $expectedResponseDataCreator): void
    {
        $source = $sourceCreator(self::$authenticationConfiguration);
        if ($source instanceof RunSource) {
            $this->sourceRepository->save($source->getParent());
        }

        $this->sourceRepository->save($source);
        self::assertNull($source->getDeletedAt());

        $sourceId = $source->getId();

        self::assertSame(1, $this->sourceRepository->count(['id' => $source->getId()]));
        if ($source instanceof RunSource && $source->getParent() instanceof SourceInterface) {
            self::assertSame(1, $this->sourceRepository->count(['id' => $source->getParent()->getId()]));
        }

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId()
        );

        $this->entityManager->clear();
        $source = $this->sourceRepository->find($sourceId);
        \assert($source instanceof SourceInterface);

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);

        $retrievedSource = $this->sourceRepository->find($sourceId);
        self::assertInstanceOf(SourceInterface::class, $retrievedSource);
        self::assertNotNull($retrievedSource->getDeletedAt());

        if ($retrievedSource instanceof RunSource) {
            $parent = $retrievedSource->getParent();
            if ($parent instanceof SourceInterface) {
                self::assertNull($parent->getDeletedAt());
            }
        }
    }

    public function testDeleteFileSourceDeletesFileSourceFiles(): void
    {
        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);

        $fileSource = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'file source label',
        );
        \assert($fileSource instanceof FileSource);

        $this->sourceRepository->save($fileSource);

        $sourceRelativePath = $fileSource->getDirectoryPath();
        $fileRelativePath = $sourceRelativePath . '/file.yaml';

        $fileSourceStorage->write($fileRelativePath, '- content');

        self::assertTrue($fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertTrue($fileSourceStorage->fileExists($fileRelativePath));

        $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $fileSource->getId()
        );

        self::assertFalse($fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertFalse($fileSourceStorage->fileExists($fileRelativePath));
    }

    public function testDeleteIsIdempotent(): void
    {
        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
        );
        $this->sourceRepository->save($source);

        $deletedAt = new \DateTimeImmutable('1978-05-02');
        $source->setDeletedAt($deletedAt);
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId()
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        \assert(is_array($responseData));

        self::assertSame((int) $deletedAt->format('U'), $responseData['deleted_at']);
    }
}
