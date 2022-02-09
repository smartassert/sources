<?php

declare(strict_types=1);

namespace App\Request;

class InvalidSourceTypeRequest implements SourceRequestInterface
{
    public function __construct(
        private string $sourceType,
    ) {
    }

    public function getType(): string
    {
        return $this->sourceType;
    }
}
