<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

class NonAbsolutePathException extends \Exception implements FilePathExceptionInterface
{
    public function __construct(
        private string $path,
        ?\Throwable $previous = null
    ) {
        parent::__construct(sprintf('Path "%s" is not absolute"', $path), 0, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
