<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Model\SourceFile;
use App\Model\SourceFileCollection;
use App\Services\FileStoreInterface;
use App\Services\SerializableSourceLister;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SerializableSourceListerTest extends WebTestCase
{
    private SerializableSourceLister $sourceLister;
    private FileStoreInterface $fileSourceStore;
    private string $fileSourcePath;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceSerializer = self::getContainer()->get(SerializableSourceLister::class);
        \assert($runSourceSerializer instanceof SerializableSourceLister);
        $this->sourceLister = $runSourceSerializer;

        $filesourceStore = self::getContainer()->get('app.services.file_store_manager.file_source');
        \assert($filesourceStore instanceof FileStoreInterface);
        $this->fileSourceStore = $filesourceStore;

        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);

        $fileSource = new FileSource(UserId::create(), 'file source label');
        $this->fileSourcePath = (string) $fileSource;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);

        $fixtureCreator->copySetTo('Source/txt', $fileSourceStorage, (string) $fileSource);
        $fixtureCreator->copySetTo('Source/yml_yaml_valid', $fileSourceStorage, (string) $fileSource);
    }

    /**
     * @dataProvider listDataProvider
     *
     * @param string[] $expectedSourceFilePaths
     */
    public function testList(string $path, array $expectedSourceFilePaths): void
    {
        $path = str_replace('{{ fileSourcePath }}', $this->fileSourcePath, $path);

        $collection = $this->sourceLister->list($this->fileSourceStore, $path);

        self::assertInstanceOf(SourceFileCollection::class, $collection);
        self::assertCount(count($expectedSourceFilePaths), $collection);

        foreach ($collection as $sourceFileIndex => $sourceFile) {
            self::assertInstanceOf(SourceFile::class, $sourceFile);
            self::assertSame($this->fileSourceStore, $sourceFile->fileStore);

            $expectedSourceFilePath = str_replace(
                '{{ fileSourcePath }}',
                $this->fileSourcePath,
                $expectedSourceFilePaths[$sourceFileIndex]
            );

            self::assertSame($expectedSourceFilePath, $sourceFile->path);
        }
    }

    /**
     * @return array<mixed>
     */
    public function listDataProvider(): array
    {
        return [
            'path {{ fileSourcePath }}' => [
                'path' => '{{ fileSourcePath }}',
                'expectedSourceFilePaths' => [
                    '{{ fileSourcePath }}/directory/file3.yml',
                    '{{ fileSourcePath }}/file1.yaml',
                    '{{ fileSourcePath }}/file2.yml',
                ],
            ],
            'path /{{ fileSourcePath }}' => [
                'path' => '/{{ fileSourcePath }}',
                'expectedSourceFilePaths' => [
                    '{{ fileSourcePath }}/directory/file3.yml',
                    '{{ fileSourcePath }}/file1.yaml',
                    '{{ fileSourcePath }}/file2.yml',
                ],
            ],
            'path {{ fileSourcePath }}/directory' => [
                'path' => '{{ fileSourcePath }}/directory',
                'expectedSourceFilePaths' => [
                    '{{ fileSourcePath }}/directory/file3.yml',
                ],
            ],
            'path {{ fileSourcePath }}/non-existent' => [
                'path' => '{{ fileSourcePath }}/non-existent',
                'expectedSourceFilePaths' => [],
            ],
        ];
    }
}