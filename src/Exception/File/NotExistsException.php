<?php

declare(strict_types=1);

namespace App\Exception\File;

class NotExistsException extends \Exception implements PathExceptionInterface
{
    public function __construct(
        private string $path,
        private ?string $context = null,
    ) {
        parent::__construct(sprintf('Path "%s" does not exist" (%s)', $path, $context));
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function withContext(string $context): self
    {
        return new NotExistsException($this->path, $context);
    }
}
