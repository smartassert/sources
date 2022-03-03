<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Services\RunSourceSerializer;
use App\Tests\Services\SourceUserIdMutator;
use League\Flysystem\FilesystemOperator;

abstract class AbstractDeleteSourceTest extends AbstractApplicationTest
{
    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;
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

    /**
     * @return array<mixed>
     */
    public function deleteSourceSuccessDataProvider(): array
    {
        return [
            Type::FILE->value => [
                'source' => new FileSource(SourceUserIdMutator::AUTHENTICATED_USER_ID_PLACEHOLDER, 'label'),
                'expectedRepositoryCount' => 0,
            ],
            Type::GIT->value => [
                'source' => new GitSource(
                    SourceUserIdMutator::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'https://example.com/repository.git'
                ),
                'expectedRepositoryCount' => 0,
            ],
            Type::RUN->value => [
                'source' => new RunSource(
                    new FileSource(SourceUserIdMutator::AUTHENTICATED_USER_ID_PLACEHOLDER, 'label')
                ),
                'expectedRepositoryCount' => 1,
            ],
        ];
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
