<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SourceRepository;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Exception\UnparseableSourceFileException;
use App\Model\SourceRepositoryInterface;
use App\Model\UserGitRepository;
use App\Services\SourceRepository\Serializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemWriter;
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
            $this->serializer->serialize(new FileSource(UserId::create(), 'file source label'))
        );
    }

    public function testSerializeNoYamlFiles(): void
    {
        $storage = self::getContainer()->get('file_source.storage');
        assert($storage instanceof FilesystemWriter);

        $source = new FileSource(UserId::create(), 'file source label');

        $this->fixtureCreator->copySetTo('Source/txt', $storage, $source->getDirectoryPath());

        self::assertSame('', $this->serializer->serialize($source));
    }

    public function testSerializeInvalidFileSourceYaml(): void
    {
        $storage = self::getContainer()->get('file_source.storage');
        assert($storage instanceof FilesystemWriter);

        $source = new FileSource(UserId::create(), 'file source label');

        $this->fixtureCreator->copySetTo('Source/yml_yaml_invalid', $storage, $source->getDirectoryPath());

        try {
            $this->serializer->serialize($source);
            self::fail(UnparseableSourceFileException::class . ' not thrown');
        } catch (UnparseableSourceFileException $exception) {
            self::assertSame('file2.yml', $exception->getPath());
            self::assertSame(
                'Unable to parse at line 1 (near "  invalid").',
                $exception->getParseException()->getMessage()
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
        return [
            'file source' => [
                'fixtureSetIdentifier' => 'Source/yml_yaml_valid',
                'sourceStorageId' => 'file_source.storage',
                'source' => new FileSource(UserId::create(), 'file source label'),
                'expectedFixturePath' => 'RunSource/source_yml_yaml_entire.yaml',
            ],
            'git repository, entire' => [
                'fixtureSetIdentifier' => 'Source/yml_yaml_valid',
                'sourceStorageId' => 'git_repository.storage',
                'source' => new UserGitRepository(
                    new GitSource(UserId::create(), 'http://example.com/repository.git', '/')
                ),
                'expectedFixturePath' => 'RunSource/source_yml_yaml_entire.yaml',
            ],
            'git repository, partial' => [
                'fixtureSetIdentifier' => 'Source/yml_yaml_valid',
                'sourceStorageId' => 'git_repository.storage',
                'source' => new UserGitRepository(
                    new GitSource(UserId::create(), 'http://example.com/repository.git', '/directory')
                ),
                'expectedFixturePath' => 'RunSource/source_yml_yaml_partial.yaml',
            ],
        ];
    }
}
