<?php

declare(strict_types=1);

namespace App\Validator;

class FilenameValidator
{
    public function isValid(string $filename): bool
    {
        return !(
            '' === $filename
            || str_contains($filename, '\\')
            || str_contains($filename, chr(0))
            || str_contains($filename, ' ')
        );
    }
}
