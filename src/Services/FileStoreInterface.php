<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\Storage\ReadException;
use App\Exception\Storage\RemoveException;
use App\Exception\Storage\WriteException;
use League\Flysystem\FilesystemException;

interface FileStoreInterface
{
    /**
     * @throws RemoveException
     */
    public function remove(string $relativePath): void;

    /**
     * @param string[] $extensions
     *
     * @throws FilesystemException
     *
     * @return string[]
     */
    public function list(string $relativePath, array $extensions = []): array;

    /**
     * @throws WriteException
     */
    public function write(string $fileRelativePath, string $content): void;

    /**
     * @throws ReadException
     */
    public function read(string $fileRelativePath): string;

    /**
     * @throws RemoveException
     */
    public function removeFile(string $fileRelativePath): void;
}
