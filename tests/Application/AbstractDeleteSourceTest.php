<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Services\RunSourceSerializer;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceOriginFactory;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;

abstract class AbstractDeleteSourceTest extends AbstractApplicationTest
{
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
     * @dataProvider deleteSourceSuccessDataProvider
     *
     * @param callable(AuthenticationConfiguration $authenticationConfiguration): SourceInterface $sourceCreator
     */
    public function testDeleteSuccess(callable $sourceCreator): void
    {
        $source = $sourceCreator(self::$authenticationConfiguration);
        $this->sourceRepository->save($source);

        $sourceId = $source->getId();

        self::assertSame(1, $this->sourceRepository->count(['id' => $source->getId()]));
        if ($source instanceof RunSource && $source->getParent() instanceof SourceInterface) {
            self::assertSame(1, $this->sourceRepository->count(['id' => $source->getParent()->getId()]));
        }

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                    );
                },
            ],
            'git source without run source' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                    );
                },
            ],
            'run source with file parent' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return new RunSource(
                        (new EntityIdFactory())->create(),
                        SourceOriginFactory::create(
                            type: 'file',
                            userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                        )
                    );
                },
            ],
            'run source with git parent' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return new RunSource(
                        (new EntityIdFactory())->create(),
                        SourceOriginFactory::create(
                            type: 'git',
                            userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                        )
                    );
                },
            ],
        ];
    }

    public function testDeleteRunSourceDeletesRunSourceFiles(): void
    {
        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);

        $fileSource = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );

        $runSource = new RunSource((new EntityIdFactory())->create(), $fileSource);

        $this->sourceRepository->save($runSource);

        $serializedRunSourcePath = $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        $runSourceStorage->write($serializedRunSourcePath, '- serialized content');

        self::assertTrue($runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertTrue($runSourceStorage->fileExists($serializedRunSourcePath));

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $fileSource->getId()
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertFalse($fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertFalse($fileSourceStorage->fileExists($fileRelativePath));
    }
}
