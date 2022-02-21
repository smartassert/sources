<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\FileStoreInterface;
use App\Tests\Model\UserId;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileStoreManagerTest extends WebTestCase
{
    /**
     * @dataProvider removeSuccessDataProvider
     */
    public function testCreateRemoveSuccess(string $storageServiceId, string $storeServiceId): void
    {
        $storage = self::getContainer()->get($storageServiceId);
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        $store = self::getContainer()->get($storeServiceId);
        self::assertInstanceOf(FileStoreInterface::class, $store);

        $relativePath = UserId::create();
        self::assertFalse($storage->directoryExists($relativePath));

        $storage->createDirectory($relativePath);
        self::assertTrue($storage->directoryExists($relativePath));

        $store->remove($relativePath);
        self::assertFalse($storage->directoryExists($relativePath));
    }

    /**
     * @return array<mixed>
     */
    public function removeSuccessDataProvider(): array
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
     * @dataProvider writeDataProvider
     */
    public function testWrite(string $fileRelativePath, string $content): void
    {
        $storage = self::getContainer()->get('file_source.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        $store = self::getContainer()->get('app.services.file_store_manager.file_source');
        self::assertInstanceOf(FileStoreInterface::class, $store);

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
        self::assertInstanceOf(FileStoreInterface::class, $store);

        $fileSource = new FileSource(UserId::create(), 'file source label');
        $fileRelativePath = $fileSource . '/' . 'file.txt';
        $content = 'file content';

        $store->write($fileRelativePath, $content);

        self::assertSame($content, $store->read($fileRelativePath));
    }
}
