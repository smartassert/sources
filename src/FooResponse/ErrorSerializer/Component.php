<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer;

readonly class Component
{
    public function __construct(
        public string $key,
        public mixed $data,
    ) {
    }
}
