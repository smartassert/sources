<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface StorageErrorInterface extends ErrorInterface
{
    public const ERROR_CLASS = 'storage';

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

    /**
     * @return array{
     *   class: 'storage',
     *   type: ?non-empty-string,
     *   location: ?non-empty-string,
     *   object_type: non-empty-string,
     *   context: array<mixed>
     * }
     */
    public function serialize(): array;
}
