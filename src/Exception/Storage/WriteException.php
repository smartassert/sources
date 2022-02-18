<?php

declare(strict_types=1);

namespace App\Exception\Storage;

use League\Flysystem\FilesystemException;

class WriteException extends AbstractStorageException
{
    public function __construct(string $path, FilesystemException $filesystemException)
    {
        parent::__construct('write', $path, $filesystemException);
    }
}
