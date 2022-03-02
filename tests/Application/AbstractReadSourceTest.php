<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;

abstract class AbstractReadSourceTest extends AbstractApplicationTest
{
    private Store $store;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $runSourceStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);
        $this->runSourceStorage = $runSourceStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testReadSuccess(): void
    {
        $serializedRunSourceFixturePath = 'RunSource/source_yml_yaml_entire.yaml';

        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $runSource = new RunSource($fileSource);
        $this->store->add($runSource);

        $this->fixtureCreator->copyTo(
            $serializedRunSourceFixturePath,
            $this->runSourceStorage,
            $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $response = $this->applicationClient->makeReadSourceRequest(
            $this->authenticationConfiguration->validToken,
            $runSource->getId()
        );

        $this->responseAsserter->assertReadSourceSuccessResponse(
            $response,
            trim($this->fixtureStorage->read($serializedRunSourceFixturePath))
        );
    }
}
