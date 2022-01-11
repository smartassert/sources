<?php

declare(strict_types=1);

namespace App\Exception\File;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class CreateException extends \Exception implements MutationExceptionInterface, PathExceptionInterface
{
    public function __construct(
        private string $path,
        private IOExceptionInterface $IOException,
        private ?string $context = null
    ) {
        parent::__construct(
            sprintf('Unable to create "%s" (%s)', $path, $context),
            0,
            $this->IOException
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function withContext(string $context): self
    {
        return new CreateException($this->path, $this->IOException, $context);
    }
}
