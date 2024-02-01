<?php

declare(strict_types=1);

namespace App\Exception;

use SmartAssert\ServiceRequest\Error\BadRequestError;
use SmartAssert\ServiceRequest\Error\DuplicateObjectError;
use SmartAssert\ServiceRequest\Error\ErrorInterface;
use SmartAssert\ServiceRequest\Error\ModifyReadOnlyEntityError;
use SmartAssert\ServiceRequest\Error\ModifyReadOnlyEntityErrorInterface;
use SmartAssert\ServiceRequest\Error\StorageError;
use SmartAssert\ServiceRequest\Error\StorageErrorInterface;
use SmartAssert\ServiceRequest\Exception\StorageExceptionInterface;
use SmartAssert\ServiceRequest\Parameter\ParameterInterface;

readonly class ErrorResponseExceptionFactory
{
    public function create(ErrorInterface $error, ?\Throwable $previous = null): ErrorResponseException
    {
        return new ErrorResponseException($error, $this->deriveStatusCode($error), $previous);
    }

    /**
     * @param non-empty-string $errorType
     */
    public function createForBadRequest(ParameterInterface $parameter, string $errorType): ErrorResponseException
    {
        return $this->create(new BadRequestError($parameter, $errorType));
    }

    public function createForDuplicateObject(ParameterInterface $parameter): ErrorResponseException
    {
        return $this->create(new DuplicateObjectError($parameter));
    }

    /**
     * @param non-empty-string $entityId
     * @param non-empty-string $entityType
     */
    public function createForModifyReadOnlyEntity(string $entityId, string $entityType): ErrorResponseException
    {
        return $this->create(new ModifyReadOnlyEntityError($entityId, $entityType));
    }

    public function createForStorageError(StorageExceptionInterface $storageException): ErrorResponseException
    {
        return $this->create(new StorageError(
            $storageException->getOperation(),
            $storageException->getObjectType(),
            $storageException->getLocation(),
            $storageException->getContext(),
        ));
    }

    /**
     * @return int<400, 599>
     */
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
