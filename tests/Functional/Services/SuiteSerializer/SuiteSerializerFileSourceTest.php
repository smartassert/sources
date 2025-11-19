<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SuiteSerializer;

use App\Entity\FileSource;
use App\Entity\SerializedSuite;
use App\Services\EntityIdFactory;
use App\Services\SuiteSerializer;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SuiteSerializerFileSourceTest extends WebTestCase
{
    private SuiteSerializer $suiteSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $fileSourceStorage;
    private FilesystemOperator $serializedSuiteStorage;
    private FilesystemOperator $fixtureStorage;

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

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;
    }

    /**
     * @param array<non-empty-string> $suiteTests
     */
    #[DataProvider('writeSuccessDataProvider')]
    public function testWriteSuccess(?string $sourceFixture, array $suiteTests, string $expectedFixture): void
    {
        $source = SourceOriginFactory::create(type: 'file');
        $suite = SuiteFactory::create(source: $source, tests: $suiteTests);
        $serializedSuite = new SerializedSuite((new EntityIdFactory())->create(), $suite, []);

        $source = $serializedSuite->getSuite()->getSource();
        \assert($source instanceof FileSource);

        if (is_string($sourceFixture)) {
            $this->fixtureCreator->copySetTo(
                $sourceFixture,
                $this->fileSourceStorage,
                $source->getDirectoryPath()
            );
        }

        $this->suiteSerializer->write($serializedSuite);

        $this->assertSerializedSuiteContent($serializedSuite, $expectedFixture);
    }

    /**
     * @return array<mixed>
     */
    public static function writeSuccessDataProvider(): array
    {
        return [
            'empty source, empty manifest' => [
                'sourceFixture' => null,
                'suiteTests' => [],
                'expectedFixture' => 'SerializedSuite/empty.yaml',
            ],
            'empty source, non-empty manifest' => [
                'sourceFixture' => null,
                'suiteTests' => ['test1.yaml', 'test2.yaml'],
                'expectedFixture' => 'SerializedSuite/empty_source_non-empty_manifest.yaml',
            ],
            'no yaml files' => [
                'sourceFixture' => 'Source/txt',
                'suiteTests' => [],
                'expectedFixture' => 'SerializedSuite/empty.yaml',
            ],
            'complete' => [
                'sourceFixture' => 'Source/yml_yaml_valid',
                'suiteTests' => ['test1.yaml', 'test2.yaml'],
                'expectedFixture' => 'SerializedSuite/suite_yml_yaml_entire.yaml',
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
}
