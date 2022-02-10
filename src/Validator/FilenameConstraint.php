<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class FilenameConstraint extends Constraint
{
    public const MESSAGE_NAME_INVALID =
        'File name must be non-empty and contain no backslash or null byte characters.';
}
