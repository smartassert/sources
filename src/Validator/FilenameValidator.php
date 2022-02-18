<?php

declare(strict_types=1);

namespace App\Validator;

class FilenameValidator
{
    public const MESSAGE_INVALID =
        'File name must be non-empty and contain no space, backslash or null byte characters.';

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
