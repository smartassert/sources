<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use App\Tests\DataProvider\DeleteSourceSuccessDataProviderTrait;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceUserIdMutator;
use League\Flysystem\FilesystemOperator;

abstract class AbstractDeleteSourceTest extends AbstractApplicationTest
{
    use DeleteSourceSuccessDataProviderTrait;

    private Store $store;
    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider deleteSourceSuccessDataProvider
     */
    public function testDeleteSuccess(SourceInterface $source, int $expectedRepositoryCount): void
    {
        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $sourceUserIdMutator->setSourceUserId($source);

        $this->store->add($source);

        self::assertGreaterThan(0, $this->sourceRepository->count([]));

        $response = $this->applicationClient->makeDeleteSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId()
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertSame($expectedRepositoryCount, $this->sourceRepository->count([]));
    }

    public function testDeleteRunSourceDeletesRunSourceFiles(): void
    {
        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);

        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $runSource = new RunSource($fileSource);

        $this->store->add($fileSource);
        $this->store->add($runSource);

        $serializedRunSourcePath = $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        $runSourceStorage->write($serializedRunSourcePath, '- serialized content');

        self::assertTrue($runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertTrue($runSourceStorage->fileExists($serializedRunSourcePath));

        $response = $this->applicationClient->makeDeleteSourceRequest(
            $this->authenticationConfiguration->validToken,
            $runSource->getId()
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertSame(1, $this->sourceRepository->count([]));
        self::assertFalse($runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertFalse($runSourceStorage->fileExists($serializedRunSourcePath));
    }

    public function testDeleteFileSourceDeletesFileSourceFiles(): void
    {
        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);

        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $filename = 'file.yaml';

        $this->store->add($fileSource);

        $sourceRelativePath = $fileSource->getDirectoryPath();
        $fileRelativePath = $sourceRelativePath . '/' . $filename;

        $fileSourceStorage->write($fileRelativePath, '- content');

        self::assertTrue($fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertTrue($fileSourceStorage->fileExists($fileRelativePath));

        $response = $this->applicationClient->makeDeleteSourceRequest(
            $this->authenticationConfiguration->validToken,
            $fileSource->getId()
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertSame(0, $this->sourceRepository->count([]));
        self::assertFalse($fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertFalse($fileSourceStorage->fileExists($fileRelativePath));
    }
}
