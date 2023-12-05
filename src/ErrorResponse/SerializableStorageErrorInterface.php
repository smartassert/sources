<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface SerializableStorageErrorInterface extends \JsonSerializable
{
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
