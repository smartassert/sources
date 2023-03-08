<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\RunSource;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Services\RunSourceSerializer;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use League\Flysystem\FilesystemOperator;

abstract class AbstractReadSourceTest extends AbstractApplicationTest
{
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $runSourceStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);
        $this->runSourceStorage = $runSourceStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;
    }

    public function testReadSuccess(): void
    {
        $serializedRunSourceFixturePath = 'RunSource/source_yml_yaml_entire.yaml';

        $idFactory = new EntityIdFactory();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $fileSource = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );
        $sourceRepository->save($fileSource);

        $runSource = new RunSource($idFactory->create(), $fileSource);
        $sourceRepository->save($runSource);

        $this->fixtureCreator->copyTo(
            $serializedRunSourceFixturePath,
            $this->runSourceStorage,
            $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $response = $this->applicationClient->makeReadSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $runSource->getId()
        );

        $this->responseAsserter->assertReadSourceSuccessResponse(
            $response,
            trim($this->fixtureStorage->read($serializedRunSourceFixturePath))
        );
    }
}
