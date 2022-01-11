<?php

declare(strict_types=1);

namespace App\Exception\File;

class OutOfScopeException extends \Exception implements PathExceptionInterface
{
    private ?string $context;

    public function __construct(
        private string $path,
        private string $basePath
    ) {
        parent::__construct(sprintf('Path "%s" outside the scope of base path "%s"', $path, $basePath));
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

    public function withContext(string $context): self
    {
        $new = clone $this;
        $new->context = $context;

        return $new;
    }
}
