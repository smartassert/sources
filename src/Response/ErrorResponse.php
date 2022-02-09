<?php

declare(strict_types=1);

namespace App\Response;

class ErrorResponse implements ErrorResponseInterface
{
    /**
     * @param array<string, array<int|string, string>|string> $payload
     */
    public function __construct(
        private string $type,
        private array $payload,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
