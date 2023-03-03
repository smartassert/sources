<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\DirectoryListingFilter;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\SourceOriginFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DirectoryListingFilterTest extends WebTestCase
{
    private FileStoreFixtureCreator $fixtureCreator;

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;
    }

    /**
     * @dataProvider listDataProvider
     *
     * @param string[] $extensions
     * @param string[] $expectedRelativePathNames
     */
    public function testListSuccess(
        string $fixtureSet,
        string $relativePath,
        array $extensions,
        array $expectedRelativePathNames
    ): void {
        $storage = self::getContainer()->get('file_source.storage');
        \assert($storage instanceof FilesystemOperator);

        $listingFilter = self::getContainer()->get(DirectoryListingFilter::class);
        \assert($listingFilter instanceof DirectoryListingFilter);

        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);
        $fileSourceStorage->deleteDirectory($relativePath);

        $this->fixtureCreator->copySetTo('Source/' . $fixtureSet, $storage, $relativePath);

        $files = $listingFilter->filter(
            $storage->listContents($relativePath, true),
            $relativePath,
            $extensions
        )->toArray();

        self::assertCount(count($expectedRelativePathNames), $files);
        self::assertSame($expectedRelativePathNames, $files);
    }

    /**
     * @return array<mixed>
     */
    public function listDataProvider(): array
    {
        $source = SourceOriginFactory::create(type: 'file');
        \assert($source instanceof FileSource);

        $basePath = $source->getDirectoryPath();

        return [
            'source: txt, no extensions' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'extensions' => [],
                'expectedRelativePathNames' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source: txt, extensions=[txt]' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'extensions' => ['txt'],
                'expected' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source: txt, extensions=[yml, yaml]' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'extensions' => ['yml', 'yaml'],
                'expected' => [],
            ],
            'source: yml_yaml, no extensions' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => $basePath,
                'extensions' => [],
                'expected' => [
                    'directory/file3.yml',
                    'file1.yaml',
                    'file2.yml',
                ],
            ],
            'source: yml_yaml, extensions=[yml]' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => $basePath,
                'extensions' => ['yml'],
                'expected' => [
                    'directory/file3.yml',
                    'file2.yml',
                ],
            ],
            'source: mixed, extensions=[yaml]' => [
                'fixtureSet' => 'mixed',
                'relativePath' => $basePath,
                'extensions' => ['yaml'],
                'expected' => [
                    'directory/file3.yaml',
                    'file1.yaml',
                ],
            ],
        ];
    }
}
