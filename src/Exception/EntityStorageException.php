<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\IdentifyingEntityInterface;
use App\ErrorResponse\ErrorInterface as Error;
use App\ErrorResponse\HasHttpStatusCodeInterface as HasHttpCode;
use App\ErrorResponse\StorageErrorInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;

class EntityStorageException extends \Exception implements Error, HasHttpCode, StorageErrorInterface
{
    public function __construct(
        private readonly IdentifyingEntityInterface $entity,
        private readonly FilesystemException $filesystemException
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
        return 'storage';
    }

    /**
     * @return non-empty-string
     */
    public function getType(): string
    {
        $operationType = 'unknown';
        if ($this->filesystemException instanceof FilesystemOperationFailed) {
            $operationType = strtolower($this->filesystemException->operation());
            if ('' === $operationType) {
                $operationType = 'unknown';
            }
        }

        return $operationType;
    }

    public function getStatusCode(): int
    {
        return 500;
    }

    public function getLocation(): ?string
    {
        $location = null;
        if (method_exists($this->filesystemException, 'location')) {
            $location = $this->filesystemException->location();
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
            'id' => $this->entity->getId(),
            'type' => $this->entity->getEntityType()->value,
        ];
    }
}
