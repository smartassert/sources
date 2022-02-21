<?php

declare(strict_types=1);

namespace App\Validator;

class FilePathValidator
{
    public function __construct(
        private readonly FilenameValidator $filenameValidator,
    ) {
    }

    public function isValid(string $path): bool
    {
        foreach (explode('/', $path) as $part) {
            if (false === $this->filenameValidator->isValid($part)) {
                return false;
            }
        }

        return true;
    }
}
