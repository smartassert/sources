<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\StorageErrorInterface;

class StorageException extends \Exception implements StorageErrorInterface
{
    /**
     * @param ?non-empty-string     $type
     * @param non-empty-string      $objectType
     * @param ?non-empty-string     $location
     * @param array<string, scalar> $context
     */
    public function __construct(
        private readonly ?string $type,
        private readonly string $objectType,
        private readonly ?string $location,
        private readonly array $context,
        string $message,
        ?\Throwable $previous,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getClass(): string
    {
        return self::ERROR_CLASS;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getStatusCode(): int
    {
        return 500;
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
