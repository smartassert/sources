<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\UserGitRepository;
use App\Services\RunSourceSerializer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class RunSourceSerializerTest extends WebTestCase
{
    private RunSourceSerializer $runSourceSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;
    private FilesystemOperator $filesystemOperator;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceSerializer = self::getContainer()->get(RunSourceSerializer::class);
        \assert($runSourceSerializer instanceof RunSourceSerializer);
        $this->runSourceSerializer = $runSourceSerializer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureLoader = self::getContainer()->get(FixtureLoader::class);
        \assert($fixtureLoader instanceof FixtureLoader);
        $this->fixtureLoader = $fixtureLoader;

        $filesystemOperator = self::getContainer()->get('default.storage');
        \assert($filesystemOperator instanceof FilesystemOperator);
        $this->filesystemOperator = $filesystemOperator;
    }

    public function testPrepareForFileSource(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copySetTo('/Source/yml_yaml_valid', (string) $fileSource);

        $runSource = new RunSource($fileSource);
        $this->runSourceSerializer->write($runSource);

        $serializedRunSourcePath = $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->filesystemOperator->fileExists($serializedRunSourcePath));
        self::assertSame(
            $this->fixtureLoader->load('/RunSource/source_yml_yaml_entire.yaml'),
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

        $this->fixtureCreator->copySetTo('/Source/yml_yaml_valid', $userGitRepositoryPath);
        self::assertTrue($this->filesystemOperator->directoryExists($userGitRepositoryPath));

        $this->runSourceSerializer->write($runSource);

        $serializedRunSourcePath = $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->filesystemOperator->fileExists($serializedRunSourcePath));
        self::assertSame(
            $this->fixtureLoader->load($expectedSerializedFixturePath),
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
            '/RunSource/source_yml_yaml_entire.yaml',
            $runSource . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $fixturePath = $this->fixtureCreator->getFixturePath('/RunSource/source_yml_yaml_entire.yaml');
        $expected = trim((string) file_get_contents($fixturePath));

        self::assertSame($expected, $this->runSourceSerializer->read($runSource));
    }
}
