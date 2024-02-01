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
    ): StorageExceptionInterface {
        return new StorageException(
            self::OBJECT_TYPE,
            $this->createOperation($filesystemException),
            $this->createLocation($filesystemException),
            $entity->getIdentifier()->serialize(),
            $filesystemException
        );
    }

    /**
     * @return ?non-empty-string
     */
    private function createOperation(FilesystemException $filesystemException): ?string
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
