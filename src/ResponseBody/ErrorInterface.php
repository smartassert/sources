<?php

declare(strict_types=1);

namespace App\ResponseBody;

interface ErrorInterface
{
    public function getType(): string;

    /**
     * @return array<string, array<int|string, string>|string>
     */
    public function getPayload(): array;
}
