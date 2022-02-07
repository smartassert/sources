<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

class RemoveException extends \Exception implements MutationExceptionInterface, PathExceptionInterface
{
    public function __construct(
        private string $path,
        private FilesystemException $filesystemException,
    ) {
        parent::__construct(
            sprintf('Unable to remove "%s"', $path),
            0,
            $this->filesystemException
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
