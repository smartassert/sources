<?php

declare(strict_types=1);

namespace App\Exception;

class StorageException extends \Exception implements StorageExceptionInterface
{
    /**
     * @param non-empty-string      $objectType
     * @param ?non-empty-string     $operation
     * @param ?non-empty-string     $location
     * @param array<string, scalar> $context    $context
     */
    public function __construct(
        private readonly string $objectType,
        private readonly ?string $operation,
        private readonly ?string $location,
        private readonly array $context,
        ?\Throwable $previous
    ) {
        parent::__construct('', 0, $previous);
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
