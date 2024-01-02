<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use SmartAssert\ServiceRequest\Field\FieldInterface;

/**
 * @phpstan-import-type SerializedBadRequest from BadRequestErrorInterface
 */
class BadRequestError extends ErrorResponse implements BadRequestErrorInterface
{
    /**
     * @param non-empty-string $errorType
     */
    public function __construct(
        private readonly FieldInterface $field,
        private readonly string $errorType,
    ) {
        parent::__construct(BadRequestErrorInterface::ERROR_CLASS, $errorType);
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    /**
     * @return SerializedBadRequest
     */
    public function serialize(): array
    {
        return [
            'class' => BadRequestErrorInterface::ERROR_CLASS,
            'type' => $this->errorType,
            'field' => $this->field->serialize(),
        ];
    }
}
