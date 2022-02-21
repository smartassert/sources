<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class YamlFilenameConstraint extends Constraint
{
    public const MESSAGE_FILENAME_INVALID = FilenameValidator::MESSAGE_INVALID;
    public const MESSAGE_NAME_EMPTY = 'Filename without extension must not be empty.';
    public const MESSAGE_EXTENSION_INVALID = 'Filename must end with .yaml';

    public const MESSAGE_NAME_INVALID =
        'File name must be non-empty and contain no space, backslash or null byte characters.';
}
