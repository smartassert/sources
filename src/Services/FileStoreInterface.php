<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\Storage\RemoveException;

interface FileStoreInterface
{
    /**
     * @throws RemoveException
     */
    public function removeFile(string $fileRelativePath): void;
}
