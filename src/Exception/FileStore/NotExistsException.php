<?php

declare(strict_types=1);

namespace App\Exception\FileStore;

class NotExistsException extends \Exception
{
    use GetPathTrait;

    public function __construct(
        private string $path,
        ?\Throwable $previous = null
    ) {
        parent::__construct(sprintf('Path "%s" does not exist"', $path), 0, $previous);
    }
}
