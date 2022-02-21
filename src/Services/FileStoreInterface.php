<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\Storage\RemoveException;
use App\Exception\Storage\WriteException;

interface FileStoreInterface
{
    /**
     * @throws WriteException
     */
    public function write(string $fileRelativePath, string $content): void;

    /**
     * @throws RemoveException
     */
    public function removeFile(string $fileRelativePath): void;
}
