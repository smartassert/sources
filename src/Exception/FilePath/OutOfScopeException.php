<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

use App\Exception\File\PathExceptionInterface;

class OutOfScopeException extends \Exception implements PathExceptionInterface
{
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
}
