<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer;

readonly class Component
{
    /**
     * @param ?non-empty-string $key
     */
    public function __construct(
        public ?string $key = null,
        public mixed $data = null,
    ) {
    }
}
