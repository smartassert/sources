<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Services\EntityIdFactory;
use App\Services\SuiteSerializer;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use League\Flysystem\FilesystemOperator;

abstract class AbstractReadSuiteTest extends AbstractApplicationTest
{
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $serializedSuiteStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $serializedSuiteStorage = self::getContainer()->get('serialized_suite.storage');
        \assert($serializedSuiteStorage instanceof FilesystemOperator);
        $this->serializedSuiteStorage = $serializedSuiteStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        \assert($entityRemover instanceof EntityRemover);
        $entityRemover->removeAll();
    }

    public function testReadSerializedSuiteNotSerialized(): void
    {
        $idFactory = new EntityIdFactory();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);

        $serializedSuiteRepository = self::getContainer()->get(SerializedSuiteRepository::class);
        \assert($serializedSuiteRepository instanceof SerializedSuiteRepository);

        $fileSource = SourceOriginFactory::create(
            type: 'file',
            userId: self::$users->get(self::USER_1_EMAIL)->id
        );
        $sourceRepository->save($fileSource);

        $suite = new Suite($idFactory->create());
        $suite->setLabel('suite label');
        $suite->setSource($fileSource);
        $suite->setTests(['test1.yaml']);
        $suiteRepository->save($suite);

        $serializedSuite = new SerializedSuite($idFactory->create(), $suite);
        $serializedSuiteRepository->save($serializedSuite);

        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuite->id
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testReadSuccess(): void
    {
        $serializedSuiteFixturePath = 'SerializedSuite/suite_yml_yaml_entire.yaml';

        $idFactory = new EntityIdFactory();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);

        $serializedSuiteRepository = self::getContainer()->get(SerializedSuiteRepository::class);
        \assert($serializedSuiteRepository instanceof SerializedSuiteRepository);

        $fileSource = SourceOriginFactory::create(
            type: 'file',
            userId: self::$users->get(self::USER_1_EMAIL)->id
        );
        $sourceRepository->save($fileSource);

        $suite = new Suite($idFactory->create());
        $suite->setLabel('suite label');
        $suite->setSource($fileSource);
        $suite->setTests(['test1.yaml', 'test2.yaml']);
        $suiteRepository->save($suite);

        $serializedSuite = new SerializedSuite($idFactory->create(), $suite);
        $serializedSuiteRepository->save($serializedSuite);

        $this->fixtureCreator->copyTo(
            $serializedSuiteFixturePath,
            $this->serializedSuiteStorage,
            $serializedSuite->getDirectoryPath() . '/' . SuiteSerializer::SERIALIZED_FILENAME
        );

        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuite->id
        );

        $this->responseAsserter->assertReadSourceSuccessResponse(
            $response,
            trim($this->fixtureStorage->read($serializedSuiteFixturePath))
        );
    }
}
