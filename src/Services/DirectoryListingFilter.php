<?php

declare(strict_types=1);

namespace App\Services;

use League\Flysystem\DirectoryListing;
use League\Flysystem\StorageAttributes;

class DirectoryListingFilter
{
    /**
     * @param DirectoryListing<StorageAttributes> $directoryListing
     * @param string[]                            $extensions
     *
     * @return DirectoryListing<string>
     */
    public function filter(
        DirectoryListing $directoryListing,
        string $relativePath,
        array $extensions = []
    ): DirectoryListing {
        return $directoryListing
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
    }
}
