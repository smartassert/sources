<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\RunSource;
use App\Entity\SourceOriginInterface;
use App\Exception\UnserializableSourceException;
use App\Services\EntityIdFactory;
use App\Services\RunSourceSerializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileSourceFactory;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RunSourceSerializerTest extends WebTestCase
{
    private RunSourceSerializer $runSourceSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $fileSourceStorage;
    private FilesystemOperator $runSourceStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceSerializer = self::getContainer()->get(RunSourceSerializer::class);
        \assert($runSourceSerializer instanceof RunSourceSerializer);
        $this->runSourceSerializer = $runSourceSerializer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);
        $this->fileSourceStorage = $fileSourceStorage;

        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);
        $this->runSourceStorage = $runSourceStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;
    }

    public function testWriteUnserializableSource(): void
    {
        $originSource = \Mockery::mock(SourceOriginInterface::class);
        $originSource
            ->shouldReceive('getUserId')
            ->andReturn(UserId::create())
        ;

        $runSource = new RunSource((new EntityIdFactory())->create(), $originSource);

        try {
            $this->runSourceSerializer->write($runSource);
            self::fail(UnserializableSourceException::class . ' not thrown');
        } catch (UnserializableSourceException $e) {
            self::assertSame($originSource, $e->getOriginSource());
        }
    }

    public function testWriteSuccess(): void
    {
        $idFactory = new EntityIdFactory();

        $fileSource = FileSourceFactory::create();
        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_valid',
            $this->fileSourceStorage,
            $fileSource->getDirectoryPath()
        );

        $runSource = new RunSource($idFactory->create(), $fileSource);
        $this->runSourceSerializer->write($runSource);

        $serializedRunSourcePath = $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertTrue($this->runSourceStorage->fileExists($serializedRunSourcePath));
        self::assertSame(
            trim($this->fixtureStorage->read('RunSource/source_yml_yaml_entire.yaml')),
            $this->runSourceStorage->read($serializedRunSourcePath)
        );
    }

    public function testReadSuccess(): void
    {
        $idFactory = new EntityIdFactory();

        $fileSource = FileSourceFactory::create();
        $runSource = new RunSource($idFactory->create(), $fileSource);
        $this->fixtureCreator->copyTo(
            'RunSource/source_yml_yaml_entire.yaml',
            $this->runSourceStorage,
            $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $expected = $this->runSourceStorage->read(
            $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        self::assertSame(trim($expected), $this->runSourceSerializer->read($runSource));
    }
}
