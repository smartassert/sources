<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\Storage\ReadException;
use App\Exception\Storage\RemoveException;
use App\Exception\Storage\WriteException;

interface FileStoreInterface
{
    /**
     * @throws RemoveException
     */
    public function remove(string $relativePath): void;

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
