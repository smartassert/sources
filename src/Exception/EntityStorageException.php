<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\IdentifyingEntityInterface;
use App\FooResponse\EntityErrorInterface as EntityError;
use App\FooResponse\ErrorInterface as Error;
use App\FooResponse\HasHttpStatusCodeInterface as HasHttpCode;
use App\FooResponse\StorageLocationErrorInterface as StorageLocationError;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;

class EntityStorageException extends \Exception implements Error, HasHttpCode, EntityError, StorageLocationError
{
    public function __construct(
        public readonly IdentifyingEntityInterface $entity,
        public readonly FilesystemException $filesystemException
    ) {
        $message = sprintf(
            'Filesystem %s error for %s %s',
            'foo',
            $entity->getEntityType()->value,
            $entity->getId(),
        );

        parent::__construct($message, 0, $filesystemException);
    }

    public function getClass(): string
    {
        return 'entity_storage';
    }

    public function getType(): string
    {
        $operationType = 'unknown';
        if ($this->filesystemException instanceof FilesystemOperationFailed) {
            $operationType = strtolower($this->filesystemException->operation());
        }

        return $operationType;
    }

    public function getStatusCode(): int
    {
        return 500;
    }

    public function getEntity(): IdentifyingEntityInterface
    {
        return $this->entity;
    }

    public function getLocation(): ?string
    {
        $location = null;
        if (method_exists($this->filesystemException, 'location')) {
            $location = $this->filesystemException->location();
        }

        return $location;
    }
}
