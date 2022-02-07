<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

class CreateException extends \Exception implements MutationExceptionInterface, PathExceptionInterface
{
    public function __construct(
        private string $path,
        private FilesystemException $filesystemException,
    ) {
        parent::__construct(
            sprintf('Unable to create "%s"', $path),
            0,
            $filesystemException
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFilesystemException(): FilesystemException
    {
        return $this->filesystemException;
    }
}
