<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedField from FieldInterface
 *
 * @phpstan-type SerializedBadRequest array{class: 'bad_request', type: non-empty-string, field: SerializedField}
 */
interface BadRequestErrorInterface extends ErrorInterface
{
    public const ERROR_CLASS = 'bad_request';

    public function getField(): FieldInterface;

    /**
     * @return SerializedBadRequest
     */
    public function serialize(): array;
}
