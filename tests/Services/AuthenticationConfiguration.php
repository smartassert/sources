<?php

declare(strict_types=1);

namespace App\Tests\Services;

class AuthenticationConfiguration
{
    public function __construct(
        public readonly string $valid,
        public readonly string $invalid,
    ) {
    }
}
