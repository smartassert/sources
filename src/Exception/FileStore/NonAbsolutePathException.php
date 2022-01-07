<?php

declare(strict_types=1);

namespace App\Exception\FileStore;

class NonAbsolutePathException extends \Exception
{
    use GetPathTrait;

    public function __construct(
        private string $path,
        ?\Throwable $previous = null
    ) {
        parent::__construct(sprintf('Path "%s" is not absolute"', $path), 0, $previous);
    }
}
