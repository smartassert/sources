<?php

declare(strict_types=1);

namespace App\Exception;

class DuplicateFilePathException extends \Exception
{
    public function __construct(
        public readonly string $path,
    ) {
        parent::__construct();
    }
}
