<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Entity\FileSourceInterface;
use App\Model\DirectoryListing;
use App\Model\File;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;

readonly class FileSourceDirectoryLister implements DirectoryListingFactoryInterface
{
    public function __construct(
        private FileSourceReader $reader,
    ) {}

    /**
     * @throws FilesystemException
     */
    public function list(FileSourceInterface $source): DirectoryListing
    {
        $directoryListing = $this->reader->getReader()->listContents(
            location: $source->getDirectoryPath(),
            deep: true
        );

        $directoryListing = $directoryListing->filter(fn (StorageAttributes $attributes) => $attributes->isFile());

        $files = [];

        $directoryPrefix = $source->getDirectoryPath() . '/';
        $directoryPrefixLength = strlen($directoryPrefix);

        foreach ($directoryListing as $fileAttributes) {
            if ($fileAttributes instanceof FileAttributes) {
                $path = substr($fileAttributes->path(), $directoryPrefixLength);

                if ('' !== $path) {
                    $files[$path] = new File($path, (int) $fileAttributes->fileSize());
                }
            }
        }

        ksort($files);

        return new DirectoryListing($files);
    }
}
