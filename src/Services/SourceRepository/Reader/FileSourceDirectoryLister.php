<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Entity\FileSource;
use App\Model\DirectoryListing;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;

class FileSourceDirectoryLister
{
    public function __construct(
        private readonly FileSourceReader $reader,
    ) {
    }

    /**
     * @throws FilesystemException
     */
    public function list(FileSource $source): DirectoryListing
    {
        $directoryListing = $this->reader->getReader()->listContents(
            location: $source->getDirectoryPath(),
            deep: true
        );

        $directoryListing = $directoryListing->filter(fn (StorageAttributes $attributes) => $attributes->isFile());

        $paths = [];

        $directoryPrefix = $source->getDirectoryPath() . '/';
        $directoryPrefixLength = strlen($directoryPrefix);

        foreach ($directoryListing as $fileAttributes) {
            if ($fileAttributes instanceof StorageAttributes && $fileAttributes->isFile()) {
                $path = substr($fileAttributes->path(), $directoryPrefixLength);
                if ('' !== $path) {
                    $paths[] = $path;
                }
            }
        }

        return new DirectoryListing($paths);
    }
}
