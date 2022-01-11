<?php

declare(strict_types=1);

namespace App\Exception\File;

class OutOfScopeException extends \Exception implements PathExceptionInterface
{
    public function __construct(
        private string $path,
        private string $basePath,
        private ?string $context = null
    ) {
        parent::__construct(sprintf(
            'Path "%s" outside the scope of base path "%s" (%s)',
            $path,
            $basePath,
            $context
        ));
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function withContext(string $context): self
    {
        return new OutOfScopeException($this->path, $this->basePath, $context);
    }
}
