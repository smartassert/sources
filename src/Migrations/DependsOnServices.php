<?php

declare(strict_types=1);

namespace App\Migrations;

#[\Attribute]
class DependsOnServices
{
    /**
     * @param string[] $services
     */
    public function __construct(
        private array $services
    ) {
    }

    /**
     * @return string[]
     */
    public function getServices(): array
    {
        return $this->services;
    }
}
