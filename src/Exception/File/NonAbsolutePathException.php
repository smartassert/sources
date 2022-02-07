<?php

declare(strict_types=1);

namespace App\Exception\File;

class NonAbsolutePathException extends \Exception implements PathExceptionInterface
{
    public function __construct(
        private string $path,
    ) {
        parent::__construct(sprintf('Path "%s" is not absolute"', $path));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
