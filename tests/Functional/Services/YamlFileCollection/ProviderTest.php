<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\YamlFileCollection;

use App\Entity\FileSource;
use App\Services\YamlFileCollection\Factory;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use League\Flysystem\FilesystemOperator;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProviderTest extends WebTestCase
{
    private FileStoreFixtureCreator $fixtureCreator;
    private Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $factory = self::getContainer()->get(Factory::class);
        \assert($factory instanceof Factory);
        $this->factory = $factory;
    }

    /**
     * @dataProvider getYamlFilesSuccessDataProvider
     *
     * @param array<non-empty-string> $manifestPaths
     * @param YamlFile[]              $expectedYamlFiles
     */
    public function testGetYamlFilesSuccess(
        string $fixtureSet,
        string $relativePath,
        array $manifestPaths,
        array $expectedYamlFiles
    ): void {
        $storage = self::getContainer()->get('file_source.storage');
        \assert($storage instanceof FilesystemOperator);
        $storage->deleteDirectory($relativePath);

        $this->fixtureCreator->copySetTo('Source/' . $fixtureSet, $storage, $relativePath);

        $provider = $this->factory->create($storage, $relativePath, $manifestPaths);
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
    public function getYamlFilesSuccessDataProvider(): array
    {
        $source = SourceOriginFactory::create(type: 'file');
        \assert($source instanceof FileSource);

        $basePath = $source->getDirectoryPath();

        return [
            'source: txt, empty manifest paths' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'manifestPaths' => [],
                'expectedYamlFiles' => [
                    YamlFile::create('manifest.yaml', ''),
                ],
            ],
            'source: yml_yaml' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => $basePath,
                'manifestPaths' => ['file1.yaml', 'file2.yml'],
                'expectedYamlFiles' => [
                    YamlFile::create('manifest.yaml', '- file1.yaml' . "\n" . '- file2.yml'),
                    YamlFile::create('directory/file3.yml', '- "file 3 line 1"'),
                    YamlFile::create('file1.yaml', '- "file 1 line 1"' . "\n" . '- "file 1 line 2"'),
                    YamlFile::create('file2.yml', '- "file 2 line 1"' . "\n" . '- "file 2 line 2"'),
                ],
            ],
            'source: mixed' => [
                'fixtureSet' => 'mixed',
                'relativePath' => $basePath,
                'manifestPaths' => ['directory/file3.yaml'],
                'expectedYamlFiles' => [
                    YamlFile::create('manifest.yaml', '- directory/file3.yaml'),
                    YamlFile::create('directory/file3.yaml', 'File Three'),
                    YamlFile::create('file1.yaml', 'File One'),
                ],
            ],
        ];
    }
}
