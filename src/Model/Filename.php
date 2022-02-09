<?php

declare(strict_types=1);

namespace App\Model;

class Filename implements \Stringable
{
    public function __construct(
        private string $value
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isValid(): bool
    {
        return
            '' !== trim($this->value)
            && !str_contains($this->value, '\\')
            && !str_contains($this->value, chr(0));
    }
}
