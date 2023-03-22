<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SuiteSerializer;

use App\Entity\GitSource;
use App\Entity\SerializedSuite;
use App\Model\UserGitRepository;
use App\Services\DirectoryListingFilter;
use App\Services\EntityIdFactory;
use App\Services\SourceRepository\Factory\Factory as SourceRepositoryFactory;
use App\Services\SourceRepository\Reader\Provider as SourceRepositoryReaderProvider;
use App\Services\SuiteSerializer;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use SmartAssert\WorkerJobSource\JobSourceSerializer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Parser as YamlParser;

class SuiteSerializerGitSourceTest extends WebTestCase
{
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $gitRepositoryStorage;
    private FilesystemOperator $serializedSuiteStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $gitRepositoryStorage = self::getContainer()->get('git_repository.storage');
        \assert($gitRepositoryStorage instanceof FilesystemOperator);
        $this->gitRepositoryStorage = $gitRepositoryStorage;

        $serializedSuiteStorage = self::getContainer()->get('serialized_suite.storage');
        \assert($serializedSuiteStorage instanceof FilesystemOperator);
        $this->serializedSuiteStorage = $serializedSuiteStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;
    }

    /**
     * @dataProvider writeSuccessDataProvider
     */
    public function testWriteSuccess(GitSource $source, string $expectedFixture): void
    {
        $idFactory = new EntityIdFactory();

        $suite = SuiteFactory::create(source: $source, tests: ['test1.yaml', 'test2.yaml']);
        $serializedSuite = new SerializedSuite($idFactory->create(), $suite, []);
        $sourceRepository = new UserGitRepository($idFactory->create(), $source);

        $sourceRepositoryFactory = \Mockery::mock(SourceRepositoryFactory::class);

        $sourceRepositoryFactory
            ->shouldReceive('create')
            ->with($source, [])
            ->andReturn($sourceRepository)
        ;

        $sourceRepositoryFactory
            ->shouldReceive('remove')
            ->with($sourceRepository)
        ;

        $suiteSerializer = $this->createSuiteSerializer($sourceRepositoryFactory);

        $this->fixtureCreator->copySetTo(
            'Source/yml_yaml_valid',
            $this->gitRepositoryStorage,
            $sourceRepository->getDirectoryPath()
        );

        $suiteSerializer->write($serializedSuite);

        $this->assertSerializedSuiteContent($serializedSuite, $expectedFixture);
    }

    /**
     * @return array<mixed>
     */
    public function writeSuccessDataProvider(): array
    {
        return [
            'full' => [
                'source' => SourceOriginFactory::create(type: 'git'),
                'expectedFixture' => 'SerializedSuite/suite_yml_yaml_entire.yaml',
            ],
            'partial' => [
                'source' => SourceOriginFactory::create(type: 'git', path: '/directory'),
                'expectedFixture' => 'SerializedSuite/suite_yml_yaml_partial.yaml',
            ],
        ];
    }

    private function assertSerializedSuiteContent(SerializedSuite $serializedSuite, string $expectedFixture): void
    {
        $serializedSuitePath = $serializedSuite->getDirectoryPath() . '/' . SuiteSerializer::SERIALIZED_FILENAME;

        self::assertTrue($this->serializedSuiteStorage->directoryExists($serializedSuite->getDirectoryPath()));
        self::assertTrue($this->serializedSuiteStorage->fileExists($serializedSuitePath));

        self::assertSame(
            trim($this->fixtureStorage->read($expectedFixture)),
            $this->serializedSuiteStorage->read($serializedSuitePath)
        );
    }

    private function createSuiteSerializer(SourceRepositoryFactory $sourceRepositoryFactory): SuiteSerializer
    {
        $serializedSuiteStorage = self::getContainer()->get('serialized_suite.storage');
        \assert($serializedSuiteStorage instanceof FilesystemReader);
        \assert($serializedSuiteStorage instanceof FilesystemWriter);

        $sourceRepositoryReaderProvider = self::getContainer()->get(SourceRepositoryReaderProvider::class);
        \assert($sourceRepositoryReaderProvider instanceof SourceRepositoryReaderProvider);

        $jobSourceSerializer = self::getContainer()->get(JobSourceSerializer::class);
        \assert($jobSourceSerializer instanceof JobSourceSerializer);

        $yamlParser = self::getContainer()->get(YamlParser::class);
        \assert($yamlParser instanceof YamlParser);

        $listingFilter = self::getContainer()->get(DirectoryListingFilter::class);
        \assert($listingFilter instanceof DirectoryListingFilter);

        return new SuiteSerializer(
            $serializedSuiteStorage,
            $serializedSuiteStorage,
            $sourceRepositoryFactory,
            $sourceRepositoryReaderProvider,
            $jobSourceSerializer,
            $yamlParser,
            $listingFilter
        );
    }
}
