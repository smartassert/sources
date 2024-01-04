<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedField from FieldInterface
 */
interface RequestFieldErrorInterface extends ErrorInterface
{
    public function getField(): FieldInterface;

    /**
     * @return array{class: non-empty-string, field: SerializedField}
     */
    public function serialize(): array;
}
