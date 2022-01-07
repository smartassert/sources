<?php

declare(strict_types=1);

namespace App\Exception\FileStore;

class OutOfScopeException extends \Exception
{
    use GetPathTrait;

    public function __construct(
        private string $path,
        private string $basePath
    ) {
        parent::__construct(
            sprintf('Path "%s" outside the scope of base path "%s"', $path, $basePath)
        );
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
