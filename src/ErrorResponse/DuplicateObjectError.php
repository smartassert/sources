<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

/**
 * @phpstan-import-type SerializedDuplicateObjectError from DuplicateObjectErrorInterface
 */
class DuplicateObjectError extends ErrorResponse implements DuplicateObjectErrorInterface
{
    public function __construct(
        private readonly FieldInterface $field,
    ) {
        parent::__construct(DuplicateObjectErrorInterface::ERROR_CLASS);
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
