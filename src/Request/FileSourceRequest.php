<?php

declare(strict_types=1);

namespace App\Request;

class FileSourceRequest
{
    public const PARAMETER_LABEL = 'label';

    public function __construct(
        public readonly string $label,
    ) {
    }
}
