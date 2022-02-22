<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\FileLister;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;

class FileStoreFixtureCreator
{
    public function __construct(
        private FileLister $fileLister,
        private FilesystemReader $fixturesReader,
    ) {
    }

    public function copySetTo(
        string $originRelativePath,
        FilesystemWriter $storage,
        string $targetRelativeDirectory
    ): void {
        $originFiles = $this->fileLister->list($this->fixturesReader, $originRelativePath);

        foreach ($originFiles as $fileRelativePath) {
            $this->copyTo(
                $originRelativePath . '/' . $fileRelativePath,
                $storage,
                $targetRelativeDirectory . '/' . $fileRelativePath
            );
        }
    }

    public function copyTo(string $originRelativePath, FilesystemWriter $storage, string $targetRelativePath): void
    {
        $storage->write($targetRelativePath, $this->fixturesReader->read($originRelativePath));
    }
}
