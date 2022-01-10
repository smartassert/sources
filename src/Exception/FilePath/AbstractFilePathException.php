<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

use App\Exception\File\PathExceptionInterface;

abstract class AbstractFilePathException extends \Exception implements PathExceptionInterface
{
    public function __construct(
        private string $path,
        string $message,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
