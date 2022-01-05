<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\DirectoryDuplicator;
use App\Services\FileStoreFactory;
use App\Tests\Mock\Model\MockFileLocator;
use App\Tests\Services\FileStoreManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class DirectoryDuplicatorTest extends WebTestCase
{
    private DirectoryDuplicator $directoryDuplicator;
    private FileStoreFactory $fileStoreFactory;
    private FileStoreManager $fileStoreManager;

    protected function setUp(): void
    {
        parent::setUp();

        $directoryDuplicator = self::getContainer()->get(DirectoryDuplicator::class);
        \assert($directoryDuplicator instanceof DirectoryDuplicator);
        $this->directoryDuplicator = $directoryDuplicator;

        $fileStoreFactory = self::getContainer()->get(FileStoreFactory::class);
        \assert($fileStoreFactory instanceof FileStoreFactory);
        $this->fileStoreFactory = $fileStoreFactory;

        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $this->fileStoreManager = $fileStoreManager;
    }

    public function testDuplicateSuccess(): void
    {
        $sourceRelativePath = 'source';
        $this->fileStoreManager->copyFixturesTo($sourceRelativePath);

        $source = (new MockFileLocator())
            ->withToStringCall($sourceRelativePath)
            ->withGetPathCall($sourceRelativePath)
            ->getMock()
        ;

        $targetRelativePath = 'target';
        $target = (new MockFileLocator())
            ->withToStringCall($targetRelativePath)
            ->withGetPathCall($targetRelativePath)
            ->getMock()
        ;

        $sourceFileStore = $this->fileStoreFactory->create($source);
        $targetFileStore = $this->fileStoreFactory->create($target);

        $sourcePath = (string) $sourceFileStore;
        $targetPath = (string) $targetFileStore;

        if (file_exists($targetPath) && is_dir($targetPath)) {
            (new Filesystem())->remove($targetPath);
        }

        self::assertDirectoryDoesNotExist($targetPath);

        $this->directoryDuplicator->duplicate($sourceFileStore, $targetFileStore);

        self::assertSame(scandir($sourcePath), scandir($targetPath));
    }
}
