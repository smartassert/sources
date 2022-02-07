<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

class ReadException extends \Exception implements PathExceptionInterface
{
    public function __construct(
        private string $path,
        private FilesystemException $filesystemException,
        private ?string $context = null
    ) {
        parent::__construct(
            sprintf('Unable to read file "%s" (%s)', $path, $context),
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

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function withContext(string $context): self
    {
        return new self($this->path, $this->filesystemException, $context);
    }
}
