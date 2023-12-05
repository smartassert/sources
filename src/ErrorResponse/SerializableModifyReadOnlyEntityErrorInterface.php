<?php

declare(strict_types=1);

namespace App\ErrorResponse;

/**
 * @phpstan-type SerializedModifyReadOnlyEntityError array{
 *   class: non-empty-string,
 *   entity: array{
 *     id: non-empty-string,
 *     type: non-empty-string
 *   }
 * }
 */
interface SerializableModifyReadOnlyEntityErrorInterface extends SerializableErrorInterface
{
    /**
     * @return SerializedModifyReadOnlyEntityError
     */
    public function jsonSerialize(): array;
}
