<?php

declare(strict_types=1);

namespace App\ErrorResponse\ErrorSerializer;

readonly class Component
{
    public function __construct(
        public string $key,
        public mixed $data,
    ) {
    }
}
