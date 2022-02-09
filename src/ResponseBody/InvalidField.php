<?php

declare(strict_types=1);

namespace App\ResponseBody;

class InvalidField
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly string $message,
    ) {
    }
}
