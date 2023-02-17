<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\RunSourceSerializer;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceProvider;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;

abstract class AbstractDeleteSourceTest extends AbstractApplicationTest
{
    private SourceRepository $sourceRepository;
    private SourceProvider $sourceProvider;
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

        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $sourceProvider->setUserId(self::$authenticationConfiguration->getUser(self::USER_EMAIL)->id);
        $sourceProvider->initialize();
        $this->sourceProvider = $sourceProvider;
    }

    /**
     * @dataProvider deleteSourceSuccessDataProvider
     */
    public function testDeleteSuccess(string $sourceIdentifier): void
    {
        $source = $this->sourceProvider->get($sourceIdentifier);
        $sourceId = $source->getId();

        self::assertSame(1, $this->sourceRepository->count(['id' => $source->getId()]));
        if ($source instanceof RunSource && $source->getParent() instanceof SourceInterface) {
            self::assertSame(1, $this->sourceRepository->count(['id' => $source->getParent()->getId()]));
        }

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $source->getId()
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);

        $this->entityManager->clear();

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

    /**
     * @return array<mixed>
     */
    public function deleteSourceSuccessDataProvider(): array
    {
        return [
            'file source without run source' => [
                'sourceIdentifier' => SourceProvider::FILE_WITHOUT_RUN_SOURCE,
            ],
            'git source without run source' => [
                'sourceIdentifier' => SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
            ],
            'run source with different file parent' => [
                'sourceIdentifier' => SourceProvider::RUN_WITH_DIFFERENT_FILE_PARENT,
            ],
            'run source with different git parent' => [
                'sourceIdentifier' => SourceProvider::RUN_WITH_DIFFERENT_GIT_PARENT,
            ],
        ];
    }

    public function testDeleteRunSourceDeletesRunSourceFiles(): void
    {
        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);

        $runSource = $this->sourceProvider->get(SourceProvider::RUN_WITH_FILE_PARENT);
        self::assertInstanceOf(RunSource::class, $runSource);

        $fileSource = $runSource->getParent();
        self::assertInstanceOf(FileSource::class, $fileSource);

        $serializedRunSourcePath = $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        $runSourceStorage->write($serializedRunSourcePath, '- serialized content');

        self::assertTrue($runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertTrue($runSourceStorage->fileExists($serializedRunSourcePath));

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $runSource->getId()
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertFalse($runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertFalse($runSourceStorage->fileExists($serializedRunSourcePath));
        self::assertSame(1, $this->sourceRepository->count(['id' => $fileSource->getId()]));
    }

    public function testDeleteFileSourceDeletesFileSourceFiles(): void
    {
        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);

        $fileSource = $this->sourceProvider->get(SourceProvider::FILE_WITHOUT_RUN_SOURCE);
        self::assertInstanceOf(FileSource::class, $fileSource);

        $sourceRelativePath = $fileSource->getDirectoryPath();
        $fileRelativePath = $sourceRelativePath . '/file.yaml';

        $fileSourceStorage->write($fileRelativePath, '- content');

        self::assertTrue($fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertTrue($fileSourceStorage->fileExists($fileRelativePath));

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $fileSource->getId()
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertFalse($fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertFalse($fileSourceStorage->fileExists($fileRelativePath));
    }
}
