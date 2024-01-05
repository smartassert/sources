<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\IdentifiedEntityInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;

class StorageExceptionFactory
{
    private const OBJECT_TYPE = 'entity';

    public function createForEntityStorageFailure(
        IdentifiedEntityInterface $entity,
        FilesystemException $filesystemException
    ): StorageException {
        $message = sprintf(
            'Filesystem %s error for %s %s',
            'foo',
            $entity->getIdentifier()->getType(),
            $entity->getIdentifier()->getId(),
        );

        return new StorageException(
            $this->createType($filesystemException),
            self::OBJECT_TYPE,
            $this->createLocation($filesystemException),
            $entity->getIdentifier()->serialize(),
            $message,
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
