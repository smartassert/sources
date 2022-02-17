<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\ReadException;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

class FileStoreManager
{
    public function __construct(
        private FilesystemOperator $filesystem,
    ) {
    }

    /**
     * @throws RemoveException
     */
    public function remove(string $relativePath): void
    {
        try {
            $this->filesystem->deleteDirectory($relativePath);
        } catch (FilesystemException $filesystemException) {
            throw new RemoveException($relativePath, $filesystemException);
        }
    }

    /**
     * @param string[] $extensions
     *
     * @throws FilesystemException
     *
     * @return string[]
     */
    public function list(string $relativePath, array $extensions = []): array
    {
        $directoryListing = $this->filesystem
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

    /**
     * @throws WriteException
     */
    public function write(string $fileRelativePath, string $content): void
    {
        try {
            $this->filesystem->write($fileRelativePath, $content);
        } catch (FilesystemException $e) {
            throw new WriteException($fileRelativePath, $e);
        }
    }

    /**
     * @throws ReadException
     */
    public function read(string $fileRelativePath): string
    {
        try {
            return $this->filesystem->read($fileRelativePath);
        } catch (FilesystemException $e) {
            throw new ReadException($fileRelativePath, $e);
        }
    }

    /**
     * @throws RemoveException
     */
    public function removeFile(string $fileRelativePath): void
    {
        try {
            $this->filesystem->delete($fileRelativePath);
        } catch (FilesystemException $e) {
            throw new RemoveException($fileRelativePath, $e);
        }
    }
}
