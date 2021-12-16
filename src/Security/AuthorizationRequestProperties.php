<?php

declare(strict_types=1);

namespace App\Security;

class AuthorizationRequestProperties
{
    public function __construct(
        private string $headerKey,
        private string $valuePrefix,
    ) {
    }

    public function getHeaderKey(): string
    {
        return $this->headerKey;
    }

    public function getValuePrefix(): string
    {
        return $this->valuePrefix;
    }
}
