<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\FileStoreAssetRemover;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileStoreAssetRemoverTest extends WebTestCase
{
    private FileStoreAssetRemover $fileStoreAssetRemover;
    private string $basePath;
    private FileStoreManager $fileStoreManager;

    protected function setUp(): void
    {
        parent::setUp();

        $fileStoreAssetRemover = self::getContainer()->get(FileStoreAssetRemover::class);
        \assert($fileStoreAssetRemover instanceof FileStoreAssetRemover);
        $this->fileStoreAssetRemover = $fileStoreAssetRemover;

        $basePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($basePath));
        $this->basePath = $basePath;

        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $this->fileStoreManager = $fileStoreManager;
    }

    public function testRemoveSuccess(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $fileSourceAbsolutePath = $this->basePath . '/' . $fileSource;

        $this->fileStoreManager->copyFixturesTo($fileSource->getPath());
        self::assertDirectoryExists($fileSourceAbsolutePath);

        $result = $this->fileStoreAssetRemover->remove($fileSource);

        self::assertTrue($result);
        self::assertDirectoryDoesNotExist($fileSourceAbsolutePath);
    }
}
