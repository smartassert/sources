<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\IdentifiedEntityInterface;
use App\ErrorResponse\StorageErrorInterface as StorageError;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;

class EntityStorageException extends \Exception implements StorageError
{
    public function __construct(
        private readonly IdentifiedEntityInterface $entity,
        private readonly FilesystemException $filesystemException
    ) {
        $message = sprintf(
            'Filesystem %s error for %s %s',
            'foo',
            $entity->getIdentifier()->getEntityType(),
            $entity->getIdentifier()->getId(),
        );

        parent::__construct($message, 0, $filesystemException);
    }

    public function getClass(): string
    {
        return 'storage';
    }

    /**
     * @return ?non-empty-string
     */
    public function getType(): ?string
    {
        $operationType = null;
        if ($this->filesystemException instanceof FilesystemOperationFailed) {
            $operationType = strtolower($this->filesystemException->operation());
            if ('' === $operationType) {
                $operationType = null;
            }
        }

        return $operationType;
    }

    public function getStatusCode(): int
    {
        return 500;
    }

    /**
     * @return ?non-empty-string
     */
    public function getLocation(): ?string
    {
        $location = null;
        if (method_exists($this->filesystemException, 'location')) {
            $location = trim($this->filesystemException->location());
            if ('' === $location) {
                $location = null;
            }
        }

        return $location;
    }

    public function getObjectType(): string
    {
        return 'entity';
    }

    public function getContext(): array
    {
        return [
            'id' => $this->entity->getIdentifier()->getId(),
            'type' => $this->entity->getIdentifier()->getEntityType(),
        ];
    }

    public function serialize(): array
    {
        return [
            'class' => $this->getClass(),
            'type' => $this->getType(),
            'location' => $this->getLocation(),
            'object_type' => $this->getObjectType(),
            'context' => $this->getContext(),
        ];
    }
}
