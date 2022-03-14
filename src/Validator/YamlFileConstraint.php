<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class YamlFileConstraint extends Constraint
{
    public const MESSAGE_CONTENT_EMPTY = 'File content must not be empty.';
    public const MESSAGE_CONTENT_PARSE_ERROR = 'Content must be valid YAML: {{ exception }}';
}
