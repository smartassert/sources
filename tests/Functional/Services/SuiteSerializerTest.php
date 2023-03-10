<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\SerializedSuite;
use App\Entity\SourceOriginInterface;
use App\Entity\Suite;
use App\Exception\UnserializableSourceException;
use App\Services\EntityIdFactory;
use App\Services\SuiteSerializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SuiteSerializerTest extends WebTestCase
{
    private SuiteSerializer $suiteSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $fileSourceStorage;
    private FilesystemOperator $serializedSuiteStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $suiteSerializer = self::getContainer()->get(SuiteSerializer::class);
        \assert($suiteSerializer instanceof SuiteSerializer);
        $this->suiteSerializer = $suiteSerializer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);
        $this->fileSourceStorage = $fileSourceStorage;

        $serializedSuiteStorage = self::getContainer()->get('serialized_suite.storage');
        \assert($serializedSuiteStorage instanceof FilesystemOperator);
        $this->serializedSuiteStorage = $serializedSuiteStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;
    }

    public function testWriteUnserializableSource(): void
    {
        $idFactory = new EntityIdFactory();

        $fileSource = \Mockery::mock(SourceOriginInterface::class);
        $fileSource
            ->shouldReceive('getUserId')
            ->andReturn(UserId::create())
        ;

        $suite = new Suite($idFactory->create());
        $suite->setLabel('suite label');
        $suite->setSource($fileSource);
        $suite->setTests(['test1.yaml', 'test2.yaml']);

        $serializedSuite = new SerializedSuite($idFactory->create(), $suite, []);

        try {
            $this->suiteSerializer->write($serializedSuite);
            self::fail(UnserializableSourceException::class . ' not thrown');
        } catch (UnserializableSourceException $e) {
            self::assertSame($fileSource, $e->getOriginSource());
        }
    }

    public function testWriteSuccess(): void
    {
        $serializedSuite = $this->createSerializedSuite();
        $fileSource = $serializedSuite->suite->getSource();
        \assert($fileSource instanceof FileSource);

        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_valid',
            $this->fileSourceStorage,
            $fileSource->getDirectoryPath()
        );

        $this->suiteSerializer->write($serializedSuite);

        $serializedSuitePath = $serializedSuite->getDirectoryPath() . '/' . SuiteSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->serializedSuiteStorage->directoryExists($serializedSuite->getDirectoryPath()));
        self::assertTrue($this->serializedSuiteStorage->fileExists($serializedSuitePath));
        self::assertSame(
            trim($this->fixtureStorage->read('SerializedSuite/suite_yml_yaml_entire.yaml')),
            $this->serializedSuiteStorage->read($serializedSuitePath)
        );
    }

    public function testReadSuccess(): void
    {
        $serializedSuite = $this->createSerializedSuite();

        $this->fixtureCreator->copyTo(
            'SerializedSuite/suite_yml_yaml_entire.yaml',
            $this->serializedSuiteStorage,
            $serializedSuite->getDirectoryPath() . '/' . SuiteSerializer::SERIALIZED_FILENAME
        );

        $expected = $this->serializedSuiteStorage->read(
            $serializedSuite->getDirectoryPath() . '/' . SuiteSerializer::SERIALIZED_FILENAME
        );

        self::assertSame(trim($expected), $this->suiteSerializer->read($serializedSuite));
    }

    private function createSerializedSuite(): SerializedSuite
    {
        $idFactory = new EntityIdFactory();

        $fileSource = SourceOriginFactory::create(type: 'file');

        $suite = new Suite($idFactory->create());
        $suite->setLabel('suite label');
        $suite->setSource($fileSource);
        $suite->setTests(['test1.yaml', 'test2.yaml']);

        return new SerializedSuite($idFactory->create(), $suite, []);
    }
}
