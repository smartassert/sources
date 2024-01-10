<?php

declare(strict_types=1);

namespace App\Exception;

use SmartAssert\ServiceRequest\Error\BadRequestError;
use SmartAssert\ServiceRequest\Error\DuplicateObjectError;
use SmartAssert\ServiceRequest\Error\ErrorInterface;
use SmartAssert\ServiceRequest\Error\ModifyReadOnlyEntityErrorInterface;
use SmartAssert\ServiceRequest\Error\StorageErrorInterface;
use SmartAssert\ServiceRequest\Field\FieldInterface;

class ErrorResponseExceptionFactory
{
    public function create(ErrorInterface $error, ?\Throwable $previous = null): ErrorResponseException
    {
        return new ErrorResponseException($error, $this->deriveStatusCode($error), $previous);
    }

    /**
     * @param non-empty-string $errorType
     */
    public function createForBadRequest(FieldInterface $field, string $errorType): ErrorResponseException
    {
        return $this->create(new BadRequestError($field, $errorType));
    }

    public function createForDuplicateObject(FieldInterface $field): ErrorResponseException
    {
        return $this->create(new DuplicateObjectError($field));
    }

    private function deriveStatusCode(ErrorInterface $error): int
    {
        if ($error instanceof ModifyReadOnlyEntityErrorInterface) {
            return 405;
        }

        if ($error instanceof StorageErrorInterface) {
            return 500;
        }

        return 400;
    }
}
