<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\YamlFileCollection;

use App\Entity\FileSource;
use App\Services\DirectoryListingFilter;
use App\Services\YamlFileCollection\Provider;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Parser as YamlParser;

class ProviderTest extends WebTestCase
{
    private FileStoreFixtureCreator $fixtureCreator;
    private YamlParser $yamlParser;
    private DirectoryListingFilter $listingFilter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $yamlParser = self::getContainer()->get(YamlParser::class);
        \assert($yamlParser instanceof YamlParser);
        $this->yamlParser = $yamlParser;

        $listingFilter = self::getContainer()->get(DirectoryListingFilter::class);
        \assert($listingFilter instanceof DirectoryListingFilter);
        $this->listingFilter = $listingFilter;
    }

    /**
     * @param YamlFile[] $expectedYamlFiles
     */
    #[DataProvider('getYamlFilesSuccessDataProvider')]
    public function testGetYamlFilesSuccess(string $fixtureSet, string $relativePath, array $expectedYamlFiles): void
    {
        $storage = self::getContainer()->get('file_source.storage');
        \assert($storage instanceof FilesystemOperator);
        $storage->deleteDirectory($relativePath);

        $this->fixtureCreator->copySetTo('Source/' . $fixtureSet, $storage, $relativePath);

        $provider = new Provider($this->yamlParser, $this->listingFilter, $storage, $relativePath);

        $count = 0;

        foreach ($provider->getYamlFiles() as $index => $yamlFile) {
            ++$count;

            self::assertInstanceOf(YamlFile::class, $yamlFile);

            $expectedYamlFile = $expectedYamlFiles[$index] ?? null;
            self::assertInstanceOf(YamlFile::class, $expectedYamlFile);

            self::assertEquals($expectedYamlFile, $yamlFile);
        }

        self::assertSame(count($expectedYamlFiles), $count);
    }

    /**
     * @return array<mixed>
     */
    public static function getYamlFilesSuccessDataProvider(): array
    {
        $source = SourceOriginFactory::create(type: 'file');
        \assert($source instanceof FileSource);

        $basePath = $source->getDirectoryPath();

        return [
            'source: txt' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'expectedYamlFiles' => [],
            ],
            'source: yml_yaml' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => $basePath,
                'expectedYamlFiles' => [
                    YamlFile::create('directory/file3.yml', '- "file 3 line 1"'),
                    YamlFile::create('file1.yaml', '- "file 1 line 1"' . "\n" . '- "file 1 line 2"'),
                    YamlFile::create('file2.yml', '- "file 2 line 1"' . "\n" . '- "file 2 line 2"'),
                ],
            ],
            'source: mixed' => [
                'fixtureSet' => 'mixed',
                'relativePath' => $basePath,
                'expectedYamlFiles' => [
                    YamlFile::create('directory/file3.yaml', 'File Three'),
                    YamlFile::create('file1.yaml', 'File One'),
                ],
            ],
        ];
    }
}
