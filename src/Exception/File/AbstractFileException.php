<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

abstract class AbstractFileException extends \Exception implements FileExceptionInterface
{
    public function __construct(
        private string $action,
        private string $path,
        private FilesystemException $filesystemException,
    ) {
        parent::__construct(
            sprintf('Unable to %s file "%s"', $action, $path),
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

    public function getAction(): string
    {
        return $this->action;
    }
}
