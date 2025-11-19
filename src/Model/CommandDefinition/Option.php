<?php

declare(strict_types=1);

namespace App\Model\CommandDefinition;

class Option implements \Stringable
{
    public function __construct(
        private string $name,
        private bool $isShort,
    ) {}

    public function __toString(): string
    {
        $prefix = '-';
        if (false === $this->isShort) {
            $prefix .= '-';
        }

        return $prefix . $this->name;
    }

    public static function createShort(string $name): self
    {
        return new self($name, true);
    }

    public static function createLong(string $name): self
    {
        return new self($name, false);
    }
}
