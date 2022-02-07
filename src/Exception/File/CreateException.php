<?php

declare(strict_types=1);

namespace App\Exception\File;

use League\Flysystem\FilesystemException;

class CreateException extends \Exception implements MutationExceptionInterface, PathExceptionInterface
{
    public function __construct(
        private string $path,
        private FilesystemException $filesystemException,
        private ?string $context = null
    ) {
        parent::__construct(
            sprintf('Unable to create "%s" (%s)', $path, $context),
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

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function withContext(string $context): self
    {
        return new CreateException($this->path, $this->filesystemException, $context);
    }
}
