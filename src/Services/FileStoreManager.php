<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\Storage\RemoveException;
use App\Exception\Storage\WriteException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

class FileStoreManager implements FileStoreInterface
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
