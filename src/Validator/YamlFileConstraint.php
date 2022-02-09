<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class YamlFileConstraint extends Constraint
{
    public const MESSAGE_NAME_INVALID =
        'File name must be non-empty, '
        . 'have a .yml or .yaml extension, '
        . 'and contain no backslash or null byte characters.';
    public const MESSAGE_CONTENT_EMPTY = 'File content must not be empty.';
    public const MESSAGE_CONTENT_PARSE_ERROR = 'Content must be valid YAML: {{ exception }}';
}
