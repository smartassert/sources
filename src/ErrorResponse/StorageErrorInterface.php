<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface StorageErrorInterface extends ErrorInterface
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

    /**
     * @return array{
     *   class: non-empty-string,
     *   type: ?non-empty-string,
     *   location: ?non-empty-string,
     *   object_type: non-empty-string,
     *   context: array<mixed>
     * }
     */
    public function jsonSerialize(): array;
}
