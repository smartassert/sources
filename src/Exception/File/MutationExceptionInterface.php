<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

interface MutationExceptionInterface extends FileExceptionInterface
{
    public function getFilesystemException(): FilesystemException;
}
