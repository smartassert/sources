<?php

declare(strict_types=1);

namespace App\RequestField;

/**
 * @phpstan-type SerializableField array{
 *   name: non-empty-string,
 *   value: scalar|array<scalar>,
 *   requirements?: array{
 *     data_type: string,
 *     size?: array{
 *       minimum: int,
 *       maximum: ?int
 *     }
 *   }
 * }
 */
interface SerializableFieldInterface extends \JsonSerializable
{
    /**
     * @return SerializableField
     */
    public function jsonSerialize(): array;
}
