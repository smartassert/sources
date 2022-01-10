<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

class OutOfScopeException extends AbstractFilePathException
{
    public function __construct(
        string $path,
        private string $basePath
    ) {
        parent::__construct(
            $path,
            sprintf('Path "%s" outside the scope of base path "%s"', $path, $basePath)
        );
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
