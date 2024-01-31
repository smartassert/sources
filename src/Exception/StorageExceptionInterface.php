<?php

declare(strict_types=1);

namespace App\Exception;

interface StorageExceptionInterface extends \Throwable
{
    /**
     * @return non-empty-string
     */
    public function getObjectType(): string;

    /**
     * @return ?non-empty-string
     */
    public function getOperation(): ?string;

    /**
     * @return ?non-empty-string
     */
    public function getLocation(): ?string;

    /**
     * @return array<string, scalar> $context
     */
    public function getContext(): array;
}
