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
interface BadRequestErrorInterface extends RequestFieldErrorInterface
{
    public function getField(): FieldInterface;

    /**
     * @return SerializedBadRequestError
     */
    public function serialize(): array;
}
