<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

interface FileExceptionInterface extends \Throwable
{
    public function getPath(): string;

    public function getFilesystemException(): FilesystemException;

    public function getAction(): string;
}
