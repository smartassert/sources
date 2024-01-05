<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\DuplicateObjectErrorInterface;
use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedDuplicateObjectError from DuplicateObjectErrorInterface
 */
class DuplicateObjectException extends ErrorException implements DuplicateObjectErrorInterface
{
    public function __construct(
        private readonly FieldInterface $field,
    ) {
        parent::__construct(DuplicateObjectErrorInterface::ERROR_CLASS, null, '', 400);
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    /**
     * @return SerializedDuplicateObjectError
     */
    public function serialize(): array
    {
        return [
            'class' => DuplicateObjectErrorInterface::ERROR_CLASS,
            'field' => $this->field->jsonSerialize(),
        ];
    }
}
