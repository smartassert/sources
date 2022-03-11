<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\YamlFileProvider;

use App\Entity\FileSource;
use App\Services\YamlFileProvider\Factory;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use SmartAssert\YamlFile\Model\YamlFile;
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
     * @dataProvider provideSuccessDataProvider
     *
     * @param YamlFile[] $expectedYamlFiles
     */
    public function testProvideSuccess(
        string $fixtureSet,
        string $relativePath,
        array $expectedYamlFiles
    ): void {
        $storage = self::getContainer()->get('file_source.storage');
        \assert($storage instanceof FilesystemOperator);
        $storage->deleteDirectory($relativePath);

        $this->fixtureCreator->copySetTo('Source/' . $fixtureSet, $storage, $relativePath);

        $provider = $this->factory->create($storage, $relativePath);
        $count = 0;

        foreach ($provider->provide() as $index => $yamlFile) {
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
    public function provideSuccessDataProvider(): array
    {
        $basePath = (new FileSource(UserId::create(), ''))->getDirectoryPath();

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
                    YamlFile::create('directory/file3.yml', '- "file 3 line 1"' . "\n"),
                    YamlFile::create('file1.yaml', '- "file 1 line 1"' . "\n" . '- "file 1 line 2"' . "\n"),
                    YamlFile::create('file2.yml', '- "file 2 line 1"' . "\n" . '- "file 2 line 2"' . "\n"),
                ],
            ],
            'source: mixed' => [
                'fixtureSet' => 'mixed',
                'relativePath' => $basePath,
                'expectedYamlFiles' => [
                    YamlFile::create('directory/file3.yaml', 'File Three' . "\n"),
                    YamlFile::create('file1.yaml', 'File One' . "\n"),
                ],
            ],
        ];
    }
}
