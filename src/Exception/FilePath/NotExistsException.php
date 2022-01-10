<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

use App\Exception\File\PathExceptionInterface;

class NotExistsException extends \Exception implements PathExceptionInterface
{
    public function __construct(
        private string $path,
        ?\Throwable $previous = null
    ) {
        parent::__construct(sprintf('Path "%s" does not exist"', $path), 0, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
