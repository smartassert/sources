<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SourceRepository;

use App\Exception\UnparseableSourceFileException;
use App\Model\SourceRepositoryInterface;
use App\Model\UserGitRepository;
use App\Services\EntityIdFactory;
use App\Services\SourceRepository\Serializer;
use App\Tests\Services\FileSourceFactory;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\GitSourceFactory;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemWriter;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;
use SmartAssert\YamlFile\Exception\ProvisionException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SerializerTest extends WebTestCase
{
    private Serializer $serializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $serializer = self::getContainer()->get(Serializer::class);
        \assert($serializer instanceof Serializer);
        $this->serializer = $serializer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;
    }

    public function testSerializeEmptyFileSource(): void
    {
        self::assertSame(
            '',
            $this->serializer->serialize(FileSourceFactory::create())
        );
    }

    public function testSerializeNoYamlFiles(): void
    {
        $storage = self::getContainer()->get('file_source.storage');
        assert($storage instanceof FilesystemWriter);

        $source = FileSourceFactory::create();

        $this->fixtureCreator->copySetTo('Source/txt', $storage, $source->getDirectoryPath());

        self::assertSame('', $this->serializer->serialize($source));
    }

    public function testSerializeInvalidFileSourceYaml(): void
    {
        $storage = self::getContainer()->get('file_source.storage');
        assert($storage instanceof FilesystemWriter);

        $source = FileSourceFactory::create();

        $this->fixtureCreator->copySetTo('Source/yml_yaml_invalid', $storage, $source->getDirectoryPath());

        try {
            $this->serializer->serialize($source);
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

    /**
     * @dataProvider serializeSuccessDataProvider
     */
    public function testSerializeSuccess(
        string $fixtureSetIdentifier,
        string $sourceStorageId,
        SourceRepositoryInterface $source,
        string $expectedFixturePath
    ): void {
        $storage = self::getContainer()->get($sourceStorageId);
        assert($storage instanceof FilesystemWriter);

        $this->fixtureCreator->copySetTo($fixtureSetIdentifier, $storage, $source->getDirectoryPath());

        self::assertSame(
            trim($this->fixtureStorage->read($expectedFixturePath)),
            $this->serializer->serialize($source)
        );
    }

    /**
     * @return array<mixed>
     */
    public function serializeSuccessDataProvider(): array
    {
        $idFactory = new EntityIdFactory();

        return [
            'file source' => [
                'fixtureSetIdentifier' => 'Source/yml_yaml_valid',
                'sourceStorageId' => 'file_source.storage',
                'source' => FileSourceFactory::create(),
                'expectedFixturePath' => 'RunSource/source_yml_yaml_entire.yaml',
            ],
            'git repository, entire' => [
                'fixtureSetIdentifier' => 'Source/yml_yaml_valid',
                'sourceStorageId' => 'git_repository.storage',
                'source' => new UserGitRepository($idFactory->create(), GitSourceFactory::create(path: '/')),
                'expectedFixturePath' => 'RunSource/source_yml_yaml_entire.yaml',
            ],
            'git repository, partial' => [
                'fixtureSetIdentifier' => 'Source/yml_yaml_valid',
                'sourceStorageId' => 'git_repository.storage',
                'source' => new UserGitRepository($idFactory->create(), GitSourceFactory::create(path: '/directory')),
                'expectedFixturePath' => 'RunSource/source_yml_yaml_partial.yaml',
            ],
        ];
    }
}
