<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\UserGitRepository;
use App\Services\RunSourcePreparer;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class RunSourcePreparerTest extends WebTestCase
{
    private RunSourcePreparer $runSourcePreparer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;
    private string $fileStoreBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourcePreparer = self::getContainer()->get(RunSourcePreparer::class);
        \assert($runSourcePreparer instanceof RunSourcePreparer);
        $this->runSourcePreparer = $runSourcePreparer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureLoader = self::getContainer()->get(FixtureLoader::class);
        \assert($fixtureLoader instanceof FixtureLoader);
        $this->fixtureLoader = $fixtureLoader;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->fileStoreBasePath = $fileStoreBasePath;
    }

    public function testPrepareAndSerializeForFileSource(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copyFixtureSetTo('yml_yaml_valid', (string) $fileSource);

        $runSource = new RunSource($fileSource);
        $this->runSourcePreparer->prepareAndSerialize($runSource);

        $targetAbsolutePath = $this->fileStoreBasePath . '/' . $runSource . '/serialized.yaml';

        self::assertFileExists($targetAbsolutePath);
        self::assertSame(
            $this->fixtureLoader->load('/RunSource/source_yml_yaml_entire.yaml'),
            file_get_contents($targetAbsolutePath)
        );
    }

    /**
     * @dataProvider prepareAndSerializeForGitSourceDataProvider
     */
    public function testPrepareAndSerializeForGitSource(
        RunSource $runSource,
        UserGitRepository $userGitRepository,
        string $fixtureSetIdentifier,
        UserGitRepositoryPreparer $gitRepositoryPreparer,
        string $expectedSerializedFixturePath,
    ): void {
        ObjectReflector::setProperty(
            $this->runSourcePreparer,
            $this->runSourcePreparer::class,
            'gitRepositoryPreparer',
            $gitRepositoryPreparer
        );

        $this->fixtureCreator->copyFixtureSetTo('yml_yaml_valid', (string) $userGitRepository);

        $this->runSourcePreparer->prepareAndSerialize($runSource);

        $targetAbsolutePath = $this->fileStoreBasePath . '/' . $runSource . '/serialized.yaml';

        self::assertFileExists($targetAbsolutePath);
        self::assertSame(
            $this->fixtureLoader->load($expectedSerializedFixturePath),
            file_get_contents($targetAbsolutePath)
        );

        self::assertDirectoryDoesNotExist($this->fileStoreBasePath . '/' . $userGitRepository);
    }

    /**
     * @return array<mixed>
     */
    public function prepareAndSerializeForGitSourceDataProvider(): array
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
}
