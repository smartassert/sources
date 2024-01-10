<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\IdentifiedEntityInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;
use SmartAssert\ServiceRequest\Error\StorageError;

class StorageExceptionFactory
{
    private const OBJECT_TYPE = 'entity';

    public function createForEntityStorageFailure(
        IdentifiedEntityInterface $entity,
        FilesystemException $filesystemException
    ): StorageException {
        return new StorageException(
            new StorageError(
                $this->createType($filesystemException),
                self::OBJECT_TYPE,
                $this->createLocation($filesystemException),
                $entity->getIdentifier()->serialize(),
            ),
            $filesystemException
        );
    }

    /**
     * @return ?non-empty-string
     */
    private function createType(FilesystemException $filesystemException): ?string
    {
        $operationType = null;
        if ($filesystemException instanceof FilesystemOperationFailed) {
            $operationType = strtolower($filesystemException->operation());
            if ('' === $operationType) {
                $operationType = null;
            }
        }

        return $operationType;
    }

    /**
     * @return ?non-empty-string
     */
    private function createLocation(FilesystemException $filesystemException): ?string
    {
        $location = null;
        if (method_exists($filesystemException, 'location')) {
            $location = trim($filesystemException->location());
            if ('' === $location) {
                $location = null;
            }
        }

        return $location;
    }
}
