<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\FileLister;
use App\Services\FileStoreInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemReader;

class FileStoreFixtureCreator
{
    public function __construct(
        private FileLister $fileLister,
        private FilesystemReader $fixturesReader,
        private FileStoreInterface $fixtureFileStore,
    ) {
    }

    public function copySetTo(
        string $originRelativePath,
        FilesystemOperator $storage,
        string $targetRelativeDirectory
    ): void {
        $originFiles = $this->fileLister->list($this->fixturesReader, $originRelativePath);

        foreach ($originFiles as $fileRelativePath) {
            $originPath = $originRelativePath . '/' . $fileRelativePath;
            $targetPath = $targetRelativeDirectory . '/' . $fileRelativePath;

            $storage->write($targetPath, $this->fixtureFileStore->read($originPath));
        }
    }

    public function copyTo(string $originRelativePath, FilesystemOperator $storage, string $targetRelativePath): void
    {
        $storage->write($targetRelativePath, $this->fixtureFileStore->read($originRelativePath));
    }
}
