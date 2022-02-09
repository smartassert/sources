<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

class WriteException extends AbstractFileException
{
    public function __construct(string $path, FilesystemException $filesystemException)
    {
        parent::__construct('write', $path, $filesystemException);
    }
}
