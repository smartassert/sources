<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\ModifyReadOnlyEntityErrorInterface;

class ModifyReadOnlyEntityException extends AbstractErrorException implements ModifyReadOnlyEntityErrorInterface
{
    /**
     * @param non-empty-string $entityId
     * @param non-empty-string $entityType
     */
    public function __construct(
        private readonly string $entityId,
        private readonly string $entityType,
    ) {
        parent::__construct(
            sprintf('Cannot modify %s %s, entity is read-only', $entityType, $entityId),
            405
        );
    }

    public function getClass(): string
    {
        return ModifyReadOnlyEntityErrorInterface::ERROR_CLASS;
    }

    public function getType(): null
    {
        return null;
    }

    public function serialize(): array
    {
        return [
            'class' => $this->getClass(),
            'entity' => [
                'id' => $this->entityId,
                'type' => $this->entityType,
            ],
        ];
    }
}
