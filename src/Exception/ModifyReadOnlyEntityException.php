<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\ModifyReadOnlyEntityErrorInterface;

/**
 * @phpstan-import-type SerializedModifyReadOnlyEntityError from ModifyReadOnlyEntityErrorInterface
 */
class ModifyReadOnlyEntityException extends ErrorException implements ModifyReadOnlyEntityErrorInterface
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
            ModifyReadOnlyEntityErrorInterface::ERROR_CLASS,
            null,
            sprintf('Cannot modify %s %s, entity is read-only', $entityType, $entityId),
            405
        );
    }

    /**
     * @return SerializedModifyReadOnlyEntityError
     */
    public function serialize(): array
    {
        return [
            'class' => ModifyReadOnlyEntityErrorInterface::ERROR_CLASS,
            'entity' => [
                'id' => $this->entityId,
                'type' => $this->entityType,
            ],
        ];
    }
}
