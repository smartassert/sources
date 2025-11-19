<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\DirectoryListingFilter;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;

class FileStoreFixtureCreator
{
    public function __construct(
        private FilesystemReader $fixturesReader,
        private DirectoryListingFilter $listingFilter,
    ) {}

    public function copySetTo(
        string $originRelativePath,
        FilesystemWriter $storage,
        string $targetRelativeDirectory
    ): void {
        $originFiles = $this->listingFilter->filter(
            $this->fixturesReader->listContents($originRelativePath, true),
            $originRelativePath
        );

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
