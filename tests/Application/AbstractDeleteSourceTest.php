<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Services\RunSourceSerializer;
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
        $this->sourceRepository->save($source);

        $sourceId = $source->getId();

        self::assertSame(1, $this->sourceRepository->count(['id' => $source->getId()]));
        if ($source instanceof RunSource && $source->getParent() instanceof SourceInterface) {
            self::assertSame(1, $this->sourceRepository->count(['id' => $source->getParent()->getId()]));
        }

        $unixTimestampBeforeDeletion = time();

        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId()
        );

        $unixTimestampAfterDeletion = time();

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('deleted_at', $responseData);

        $deletedAt = $responseData['deleted_at'];
        self::assertIsInt($deletedAt);

        self::assertGreaterThanOrEqual($unixTimestampBeforeDeletion, $deletedAt);
        self::assertLessThanOrEqual($unixTimestampAfterDeletion, $deletedAt);

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

        $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $runSource->getId()
        );

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
