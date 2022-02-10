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

    public function getExtension(): string
    {
        return substr($this->value, ((int) strrpos($this->value, '.')) + 1);
    }

    public function getName(): string
    {
        $lastDotPosition = strrpos($this->value, '.');

        return false === $lastDotPosition
            ? $this->value
            : substr($this->value, 0, $lastDotPosition);
    }
}
