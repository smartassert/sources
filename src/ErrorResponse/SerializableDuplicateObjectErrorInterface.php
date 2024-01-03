<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedField from FieldInterface
 *
 * @phpstan-type SerializedDuplicateObjectError array{
 *   class: non-empty-string,
 *   field: SerializedField
 * }
 */
interface SerializableDuplicateObjectErrorInterface extends ErrorInterface
{
    /**
     * @return SerializedDuplicateObjectError
     */
    public function jsonSerialize(): array;
}
