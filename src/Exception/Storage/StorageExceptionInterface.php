<?php

declare(strict_types=1);

namespace App\Exception\Storage;

use League\Flysystem\FilesystemException;

interface StorageExceptionInterface extends \Throwable
{
    public function getPath(): string;

    public function getFilesystemException(): FilesystemException;

    public function getAction(): string;
}
