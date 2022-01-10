<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

class NotExistsException extends AbstractFilePathException
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct($path, sprintf('Path "%s" does not exist"', $path), $previous);
    }
}
