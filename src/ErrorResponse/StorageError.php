<?php

declare(strict_types=1);

namespace App\ErrorResponse;

/**
 * @phpstan-import-type SerializedStorageError from StorageErrorInterface
 */
class StorageError extends ErrorResponse implements StorageErrorInterface
{
    /**
     * @param ?non-empty-string     $type
     * @param non-empty-string      $objectType
     * @param ?non-empty-string     $location
     * @param array<string, scalar> $context
     */
    public function __construct(
        ?string $type,
        private readonly string $objectType,
        private readonly ?string $location,
        private readonly array $context,
    ) {
        parent::__construct(StorageErrorInterface::ERROR_CLASS, $type);
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return SerializedStorageError
     */
    public function serialize(): array
    {
        return [
            'class' => StorageErrorInterface::ERROR_CLASS,
            'type' => $this->getType(),
            'location' => $this->getLocation(),
            'object_type' => $this->getObjectType(),
            'context' => $this->getContext(),
        ];
    }
}
