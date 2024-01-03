<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedField from FieldInterface
 *
 * @phpstan-type SerializedBadRequestError array{
 *   class: non-empty-string,
 *   type: non-empty-string,
 *   field: SerializedField,
 * }
 */
interface SerializableBadRequestErrorInterface extends ErrorInterface
{
    /**
     * @return SerializedBadRequestError
     */
    public function jsonSerialize(): array;
}
