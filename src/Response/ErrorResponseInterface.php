<?php

declare(strict_types=1);

namespace App\Response;

interface ErrorResponseInterface
{
    public function getType(): string;

    /**
     * @return array<string, array<string, string>|string>
     */
    public function getPayload(): array;
}
