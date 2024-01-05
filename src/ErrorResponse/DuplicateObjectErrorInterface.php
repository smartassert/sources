<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedField from FieldInterface
 *
 * @phpstan-type SerializedDuplicateObjectError array{class: 'duplicate', field: SerializedField}
 */
interface DuplicateObjectErrorInterface extends FooErrorInterface
{
    public const ERROR_CLASS = 'duplicate';

    public function getField(): FieldInterface;

    /**
     * @return SerializedDuplicateObjectError
     */
    public function serialize(): array;
}
