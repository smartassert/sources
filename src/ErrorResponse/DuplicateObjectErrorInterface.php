<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedField from FieldInterface
 */
interface DuplicateObjectErrorInterface extends ErrorInterface
{
    public const ERROR_CLASS = 'duplicate';

    public function getField(): FieldInterface;

    /**
     * @return array{class: 'duplicate', field: SerializedField}
     */
    public function serialize(): array;
}
