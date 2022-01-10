<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

class NonAbsolutePathException extends AbstractFilePathException
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct($path, sprintf('Path "%s" is not absolute"', $path), $previous);
    }
}
