<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\FileStoreManager;
use League\Flysystem\FilesystemOperator;

class FileStoreFixtureCreator
{
    public function __construct(
        private FileStoreManager $fixturesFileStore,
    ) {
    }

    public function copySetTo(
        string $originRelativePath,
        FilesystemOperator $storage,
        string $targetRelativeDirectory
    ): void {
        $originFiles = $this->fixturesFileStore->list($originRelativePath);

        foreach ($originFiles as $fileRelativePath) {
            $originPath = $originRelativePath . '/' . $fileRelativePath;
            $targetPath = $targetRelativeDirectory . '/' . $fileRelativePath;

            $storage->write($targetPath, $this->fixturesFileStore->read($originPath));
        }
    }

    public function copyTo(string $originRelativePath, FilesystemOperator $storage, string $targetRelativePath): void
    {
        $storage->write($targetRelativePath, $this->fixturesFileStore->read($originRelativePath));
    }
}
