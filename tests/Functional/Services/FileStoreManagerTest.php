<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\FileStoreManager;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileStoreManagerTest extends WebTestCase
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
     * @dataProvider createRemoveSuccessDataProvider
     */
    public function testCreateRemoveSuccess(string $storageServiceId, string $storeServiceId): void
    {
        $storage = self::getContainer()->get($storageServiceId);
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        $store = self::getContainer()->get($storeServiceId);
        self::assertInstanceOf(FileStoreManager::class, $store);

        $relativePath = UserId::create();
        self::assertFalse($storage->directoryExists($relativePath));

        $store->create($relativePath);
        self::assertTrue($storage->directoryExists($relativePath));

        $store->remove($relativePath);
        self::assertFalse($storage->directoryExists($relativePath));
    }

    /**
     * @return array<mixed>
     */
    public function createRemoveSuccessDataProvider(): array
    {
        return [
            'file source store' => [
                'storageServiceId' => 'file_source.storage',
                'storeServiceId' => 'app.services.file_store_manager.file_source',
            ],
            'git repository store' => [
                'storageServiceId' => 'git_repository.storage',
                'storeServiceId' => 'app.services.file_store_manager.git_repository',
            ],
            'run source store' => [
                'storageServiceId' => 'run_source.storage',
                'storeServiceId' => 'app.services.file_store_manager.run_source',
            ],
        ];
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
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        $store = self::getContainer()->get('app.services.file_store_manager.file_source');
        self::assertInstanceOf(FileStoreManager::class, $store);

        $store->remove($relativePath);

        $this->fixtureCreator->copySetTo('Source/' . $fixtureSet, $storage, $relativePath);

        $files = $store->list($relativePath, $extensions);

        self::assertCount(count($expectedRelativePathNames), $files);
        self::assertSame($expectedRelativePathNames, $files);
    }

    /**
     * @return array<mixed>
     */
    public function listDataProvider(): array
    {
        $basePath = (string) new FileSource(UserId::create(), '');

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

    /**
     * @dataProvider writeDataProvider
     */
    public function testWrite(string $fileRelativePath, string $content): void
    {
        $storage = self::getContainer()->get('file_source.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        $store = self::getContainer()->get('app.services.file_store_manager.file_source');
        self::assertInstanceOf(FileStoreManager::class, $store);

        $store->write($fileRelativePath, $content);

        self::assertTrue($storage->fileExists($fileRelativePath));
        self::assertSame($content, $store->read($fileRelativePath));
    }

    /**
     * @return array<mixed>
     */
    public function writeDataProvider(): array
    {
        return [
            'single-level relative directory' => [
                'fileRelativePath' => UserId::create() . '/file.txt',
                'content' => md5((string) rand()),
            ],
            'multi-level relative directory, file-only file path' => [
                'fileRelativePath' => UserId::create() . '/' . UserId::create() . '/file.txt',
                'content' => md5((string) rand()),
            ],
        ];
    }

    public function testReadSuccess(): void
    {
        $store = self::getContainer()->get('app.services.file_store_manager.file_source');
        self::assertInstanceOf(FileStoreManager::class, $store);

        $fileSource = new FileSource(UserId::create(), 'file source label');
        $fileRelativePath = $fileSource . '/' . 'file.txt';
        $content = 'file content';

        $store->write($fileRelativePath, $content);

        self::assertSame($content, $store->read($fileRelativePath));
    }
}
