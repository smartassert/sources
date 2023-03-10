<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SuiteSerializer;

use App\Entity\FileSource;
use App\Entity\SerializedSuite;
use App\Entity\SourceInterface;
use App\Entity\Suite;
use App\Exception\UnparseableSourceFileException;
use App\Exception\UnserializableSourceException;
use App\Services\EntityIdFactory;
use App\Services\SuiteSerializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;
use League\Flysystem\FilesystemOperator;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;
use SmartAssert\YamlFile\Exception\ProvisionException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SuiteSerializerTest extends WebTestCase
{
    private SuiteSerializer $suiteSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $fileSourceStorage;
    private FilesystemOperator $serializedSuiteStorage;

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
    }

    public function testWriteUnserializableSource(): void
    {
        $idFactory = new EntityIdFactory();

        $source = \Mockery::mock(SourceInterface::class);
        $source
            ->shouldReceive('getUserId')
            ->andReturn(UserId::create())
        ;

        $suite = new Suite($idFactory->create());
        $suite->setLabel('suite label');
        $suite->setSource($source);
        $suite->setTests(['test1.yaml', 'test2.yaml']);

        $serializedSuite = new SerializedSuite($idFactory->create(), $suite, []);

        try {
            $this->suiteSerializer->write($serializedSuite);
            self::fail(UnserializableSourceException::class . ' not thrown');
        } catch (UnserializableSourceException $e) {
            self::assertSame($source, $e->getOriginSource());
        }
    }

    public function testWriteInvalidSourceYaml(): void
    {
        $source = SourceOriginFactory::create(type: 'file');
        \assert($source instanceof FileSource);

        $suite = SuiteFactory::create($source);
        $serializedSuite = new SerializedSuite((new EntityIdFactory())->create(), $suite, []);

        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_invalid',
            $this->fileSourceStorage,
            $source->getDirectoryPath()
        );

        try {
            $this->suiteSerializer->write($serializedSuite);
            self::fail(UnparseableSourceFileException::class . ' not thrown');
        } catch (SerializeException $exception) {
            $provisionException = $exception->getPrevious();
            self::assertInstanceOf(ProvisionException::class, $provisionException);

            $unparseableSourceFileException = $provisionException->getPrevious();
            self::assertInstanceOf(UnparseableSourceFileException::class, $unparseableSourceFileException);

            self::assertSame('file2.yml', $unparseableSourceFileException->getPath());
            self::assertSame(
                'Unable to parse at line 1 (near "  invalid").',
                $unparseableSourceFileException->getParseException()->getMessage()
            );
        }
    }

    public function testReadSuccess(): void
    {
        $source = SourceOriginFactory::create(type: 'file');
        $suite = SuiteFactory::create($source);
        $serializedSuite = new SerializedSuite((new EntityIdFactory())->create(), $suite, []);

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
}
