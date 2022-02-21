<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\FileStoreInterface;
use App\Tests\Model\UserId;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileStoreManagerTest extends WebTestCase
{
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
        self::assertSame($content, $storage->read($fileRelativePath));
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
}
