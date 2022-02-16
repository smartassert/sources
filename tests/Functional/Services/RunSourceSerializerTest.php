<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\UserGitRepository;
use App\Services\FileStoreManager;
use App\Services\RunSourceSerializer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class RunSourceSerializerTest extends WebTestCase
{
    private RunSourceSerializer $runSourceSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $filesystemOperator;
    private FileStoreManager $fixtureFileStore;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceSerializer = self::getContainer()->get(RunSourceSerializer::class);
        \assert($runSourceSerializer instanceof RunSourceSerializer);
        $this->runSourceSerializer = $runSourceSerializer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $filesystemOperator = self::getContainer()->get('default.storage');
        \assert($filesystemOperator instanceof FilesystemOperator);
        $this->filesystemOperator = $filesystemOperator;

        $fixtureFileStore = self::getContainer()->get('app.tests.services.file_store_manager.fixtures');
        \assert($fixtureFileStore instanceof FileStoreManager);
        $this->fixtureFileStore = $fixtureFileStore;
    }

    public function testPrepareForFileSource(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_valid',
            $this->filesystemOperator,
            (string) $fileSource
        );

        $runSource = new RunSource($fileSource);
        $this->runSourceSerializer->write($runSource);

        $serializedRunSourcePath = $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->filesystemOperator->fileExists($serializedRunSourcePath));
        self::assertSame(
            trim($this->fixtureFileStore->read('RunSource/source_yml_yaml_entire.yaml')),
            $this->filesystemOperator->read($serializedRunSourcePath)
        );
    }

    /**
     * @dataProvider prepareForGitSourceDataProvider
     */
    public function testPrepareForGitSource(
        RunSource $runSource,
        UserGitRepository $userGitRepository,
        string $fixtureSetIdentifier,
        UserGitRepositoryPreparer $gitRepositoryPreparer,
        string $expectedSerializedFixturePath,
    ): void {
        ObjectReflector::setProperty(
            $this->runSourceSerializer,
            $this->runSourceSerializer::class,
            'gitRepositoryPreparer',
            $gitRepositoryPreparer
        );

        $userGitRepositoryPath = (string) $userGitRepository;

        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_valid',
            $this->filesystemOperator,
            $userGitRepositoryPath
        );
        self::assertTrue($this->filesystemOperator->directoryExists($userGitRepositoryPath));

        $this->runSourceSerializer->write($runSource);

        $serializedRunSourcePath = $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->filesystemOperator->fileExists($serializedRunSourcePath));
        self::assertSame(
            trim($this->fixtureFileStore->read($expectedSerializedFixturePath)),
            $this->filesystemOperator->read($serializedRunSourcePath)
        );

        self::assertFalse($this->filesystemOperator->directoryExists($userGitRepositoryPath));
    }

    /**
     * @return array<mixed>
     */
    public function prepareForGitSourceDataProvider(): array
    {
        $gitRef = 'v1.1';
        $gitSourceEntire = new GitSource(UserId::create(), 'http://example.com/repository.git');
        $gitSourceEntireRepository = new UserGitRepository($gitSourceEntire);

        $gitSourceEntireRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $gitSourceEntireRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($gitSourceEntire, $gitRef)
            ->andReturn($gitSourceEntireRepository)
        ;

        $gitSourcePartial = new GitSource(UserId::create(), 'http://example.com/repository.git', '/directory');
        $gitSourcePartialRepository = new UserGitRepository($gitSourcePartial);

        $gitSourcePartialRepositoryPreparer = \Mockery::mock(UserGitRepositoryPreparer::class);
        $gitSourcePartialRepositoryPreparer
            ->shouldReceive('prepare')
            ->with($gitSourcePartial, $gitRef)
            ->andReturn($gitSourcePartialRepository)
        ;

        return [
            'git source, entire' => [
                'runSource' => new RunSource($gitSourceEntire, ['ref' => $gitRef]),
                'userGitRepository' => $gitSourceEntireRepository,
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'gitRepositoryPreparer' => $gitSourceEntireRepositoryPreparer,
                'expectedSerializedFixturePath' => '/RunSource/source_yml_yaml_entire.yaml',
            ],
            'git source, partial' => [
                'runSource' => new RunSource($gitSourcePartial, ['ref' => $gitRef]),
                'userGitRepository' => $gitSourcePartialRepository,
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'gitRepositoryPreparer' => $gitSourcePartialRepositoryPreparer,
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
            $this->filesystemOperator,
            $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $expected = $this->filesystemOperator->read($runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME);

        self::assertSame(trim($expected), $this->runSourceSerializer->read($runSource));
    }
}
