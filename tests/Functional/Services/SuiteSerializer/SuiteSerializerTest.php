<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SuiteSerializer;

use App\Entity\FileSource;
use App\Entity\SerializedSuite;
use App\Entity\SourceInterface;
use App\Entity\Suite;
use App\Enum\Source\Type;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Exception\UnparseableSourceFileException;
use App\Services\EntityIdFactory;
use App\Services\SuiteSerializer;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\StringFactory;
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

    #[\Override]
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
            ->shouldReceive('getId')
            ->andReturn(StringFactory::createRandom())
        ;
        $source
            ->shouldReceive('getType')
            ->andReturn(Type::FILE)
        ;

        $suite = new Suite($idFactory->create());
        $suite->setSource($source);

        $serializedSuite = new SerializedSuite($idFactory->create(), $suite, []);

        try {
            $this->suiteSerializer->write($serializedSuite);
            self::fail(NoSourceRepositoryCreatorException::class . ' not thrown');
        } catch (NoSourceRepositoryCreatorException $e) {
            self::assertSame($source, $e->source);
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
