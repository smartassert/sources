<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\IdentifyingEntityInterface;
use App\ErrorResponse\EntityErrorInterface as EntityError;
use App\ErrorResponse\SerializableModifyReadOnlyEntityErrorInterface as SerializableModifyReadOnlyEntityError;

class ModifyReadOnlyEntityException extends \Exception implements EntityError, SerializableModifyReadOnlyEntityError
{
    public function __construct(
        public readonly IdentifyingEntityInterface $entity,
    ) {
        parent::__construct(sprintf(
            'Cannot modify %s %s, entity is read-only',
            $entity->getEntityType()->value,
            $entity->getId(),
        ));
    }

    public function getStatusCode(): int
    {
        return 405;
    }

    public function getClass(): string
    {
        return 'modify_read_only';
    }

    public function getType(): null
    {
        return null;
    }

    public function getEntity(): IdentifyingEntityInterface
    {
        return $this->entity;
    }

    public function jsonSerialize(): array
    {
        return [
            'class' => $this->getClass(),
            'entity' => [
                'id' => $this->entity->getId(),
                'type' => $this->entity->getEntityType()->value,
            ],
        ];
    }
}
