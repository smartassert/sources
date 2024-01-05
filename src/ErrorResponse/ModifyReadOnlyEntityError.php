<?php

declare(strict_types=1);

namespace App\ErrorResponse;

/**
 * @phpstan-import-type SerializedModifyReadOnlyEntityError from ModifyReadOnlyEntityErrorInterface
 */
class ModifyReadOnlyEntityError extends ErrorResponse implements ModifyReadOnlyEntityErrorInterface
{
    /**
     * @param non-empty-string $entityId
     * @param non-empty-string $entityType
     */
    public function __construct(
        private readonly string $entityId,
        private readonly string $entityType,
    ) {
        parent::__construct(ModifyReadOnlyEntityErrorInterface::ERROR_CLASS);
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
