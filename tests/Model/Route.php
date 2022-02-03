<?php

declare(strict_types=1);

namespace App\Tests\Model;

class Route
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly array $parameters = [],
    ) {
    }
}
