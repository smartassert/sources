<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\UserGitRepository;
use App\Services\FileStoreInterface;
use App\Services\GitRepositoryStore;
use App\Services\RunSourceSerializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class RunSourceSerializerTest extends WebTestCase
{
    private RunSourceSerializer $runSourceSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $fileSourceStorage;
    private FilesystemOperator $gitRepositoryStorage;
    private FilesystemOperator $runSourceStorage;
    private FileStoreInterface $fixtureFileStore;

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

        $gitRepositoryStorage = self::getContainer()->get('git_repository.storage');
        \assert($gitRepositoryStorage instanceof FilesystemOperator);
        $this->gitRepositoryStorage = $gitRepositoryStorage;

        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);
        $this->runSourceStorage = $runSourceStorage;

        $fixtureFileStore = self::getContainer()->get('app.tests.services.file_store_manager.fixtures');
        \assert($fixtureFileStore instanceof FileStoreInterface);
        $this->fixtureFileStore = $fixtureFileStore;
    }

    public function testWriteFileSource(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_valid',
            $this->fileSourceStorage,
            (string) $fileSource
        );

        $runSource = new RunSource($fileSource);
        $this->runSourceSerializer->write($runSource);

        $serializedRunSourcePath = $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->runSourceStorage->directoryExists((string) $runSource));
        self::assertTrue($this->runSourceStorage->fileExists($serializedRunSourcePath));
        self::assertSame(
            trim($this->fixtureFileStore->read('RunSource/source_yml_yaml_entire.yaml')),
            $this->runSourceStorage->read($serializedRunSourcePath)
        );
    }

    /**
     * @dataProvider prepareForGitSourceDataProvider
     */
    public function testWriteGitSource(
        RunSource $runSource,
        UserGitRepository $userGitRepository,
        string $fixtureSetIdentifier,
        GitRepositoryStore $gitRepositoryStore,
        string $expectedSerializedFixturePath,
    ): void {
        ObjectReflector::setProperty(
            $this->runSourceSerializer,
            $this->runSourceSerializer::class,
            'gitRepositoryStore',
            $gitRepositoryStore
        );

        $userGitRepositoryPath = (string) $userGitRepository;

        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_valid',
            $this->gitRepositoryStorage,
            $userGitRepositoryPath
        );
        self::assertTrue($this->gitRepositoryStorage->directoryExists($userGitRepositoryPath));

        $this->runSourceSerializer->write($runSource);

        $serializedRunSourcePath = $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->runSourceStorage->fileExists($serializedRunSourcePath));
        self::assertSame(
            trim($this->fixtureFileStore->read($expectedSerializedFixturePath)),
            $this->runSourceStorage->read($serializedRunSourcePath)
        );

        self::assertFalse($this->gitRepositoryStorage->directoryExists($userGitRepositoryPath));
    }

    /**
     * @return array<mixed>
     */
    public function prepareForGitSourceDataProvider(): array
    {
        $gitRef = 'v1.1';
        $gitSourceEntire = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $gitSourceEntireRepository = new UserGitRepository($gitSourceEntire);

        $gitSourceEntireGitRepositoryStore = \Mockery::mock(GitRepositoryStore::class);
        $gitSourceEntireGitRepositoryStore
            ->shouldReceive('initialize')
            ->with($gitSourceEntire, $gitRef)
            ->andReturn($gitSourceEntireRepository)
        ;

        $gitSourcePartial = new GitSource(UserId::create(), 'http://example.com/repository.git', '/directory');
        $gitSourcePartialRepository = new UserGitRepository($gitSourcePartial);

        $gitSourcePartialGitRepositoryStore = \Mockery::mock(GitRepositoryStore::class);
        $gitSourcePartialGitRepositoryStore
            ->shouldReceive('initialize')
            ->with($gitSourcePartial, $gitRef)
            ->andReturn($gitSourcePartialRepository)
        ;

        return [
            'git source, entire' => [
                'runSource' => new RunSource($gitSourceEntire, ['ref' => $gitRef]),
                'userGitRepository' => $gitSourceEntireRepository,
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'gitRepositoryPreparer' => $gitSourceEntireGitRepositoryStore,
                'expectedSerializedFixturePath' => '/RunSource/source_yml_yaml_entire.yaml',
            ],
            'git source, partial' => [
                'runSource' => new RunSource($gitSourcePartial, ['ref' => $gitRef]),
                'userGitRepository' => $gitSourcePartialRepository,
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'gitRepositoryPreparer' => $gitSourcePartialGitRepositoryStore,
                'expectedSerializedFixturePath' => '/RunSource/source_yml_yaml_partial.yaml',
            ],
        ];
    }

    public function testReadSuccess(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $runSource = new RunSource($fileSource);
        $this->fixtureCreator->copyTo(
            'RunSource/source_yml_yaml_entire.yaml',
            $this->runSourceStorage,
            $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $expected = $this->runSourceStorage->read($runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME);

        self::assertSame(trim($expected), $this->runSourceSerializer->read($runSource));
    }
}
