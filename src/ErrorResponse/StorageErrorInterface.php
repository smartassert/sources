<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface StorageErrorInterface
{
    /**
     * @return ?non-empty-string
     */
    public function getLocation(): ?string;

    /**
     * @return array<string, scalar>
     */
    public function getContext(): array;

    /**
     * @return non-empty-string
     */
    public function getObjectType(): string;
}
