<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedField from FieldInterface
 *
 * @phpstan-type SerializedRequestFieldError array{
 *   class: non-empty-string,
 *   field: SerializedField,
 * }
 */
interface RequestFieldErrorInterface extends ErrorInterface
{
    public function getField(): FieldInterface;

    /**
     * @return SerializedRequestFieldError
     */
    public function serialize(): array;
}
