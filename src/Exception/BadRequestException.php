<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\BadRequestErrorInterface;
use App\RequestField\FieldInterface;

class BadRequestException extends AbstractErrorException implements BadRequestErrorInterface
{
    /**
     * @param non-empty-string $errorType
     */
    public function __construct(
        private readonly FieldInterface $field,
        private readonly string $errorType,
    ) {
        $message = 'bad_request: ' . $field->getName() . ' ' . $errorType;

        parent::__construct(BadRequestErrorInterface::ERROR_CLASS, $errorType, $message, 400);
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    public function serialize(): array
    {
        return [
            'class' => BadRequestErrorInterface::ERROR_CLASS,
            'type' => $this->errorType,
            'field' => $this->field->jsonSerialize(),
        ];
    }
}
