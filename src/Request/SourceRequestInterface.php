<?php

declare(strict_types=1);

namespace App\Request;

interface SourceRequestInterface
{
    public const PARAMETER_TYPE = 'type';

    public function getType(): string;
}
