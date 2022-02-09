<?php

declare(strict_types=1);

namespace App\ResponseBody;

class InvalidField
{
    public function __construct(
        private string $name,
        private string $value,
        private string $message,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
