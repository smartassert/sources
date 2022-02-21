<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\Storage\RemoveException;
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
    public function removeFile(string $fileRelativePath): void
    {
        try {
            $this->filesystem->delete($fileRelativePath);
        } catch (FilesystemException $e) {
            throw new RemoveException($fileRelativePath, $e);
        }
    }
}
