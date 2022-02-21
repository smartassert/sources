<?php

declare(strict_types=1);

namespace App\Services;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\StorageAttributes;

class FileLister
{
    /**
     * @param string[] $extensions
     *
     * @throws FilesystemException
     *
     * @return string[]
     */
    public function list(FilesystemReader $reader, string $relativePath, array $extensions = []): array
    {
        $directoryListing = $reader
            ->listContents($relativePath, true)
            ->filter(function (StorageAttributes $item) {
                return !$item->isDir();
            })
            ->filter(function (StorageAttributes $item) use ($extensions) {
                if (0 === count($extensions)) {
                    return true;
                }

                $path = $item->path();
                foreach ($extensions as $extension) {
                    if (str_ends_with($path, '.' . $extension)) {
                        return true;
                    }
                }

                return false;
            })
            ->sortByPath()
            ->map(function (StorageAttributes $item) use ($relativePath) {
                $itemPath = $item->path();
                if (str_starts_with($itemPath, $relativePath)) {
                    $itemPath = substr($itemPath, strlen($relativePath) + 1);
                }

                return $itemPath;
            })
        ;

        return $directoryListing->toArray();
    }
}
