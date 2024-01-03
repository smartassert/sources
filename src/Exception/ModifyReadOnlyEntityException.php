<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\EntityIdentifierInterface;
use App\ErrorResponse\EntityErrorInterface as EntityError;

class ModifyReadOnlyEntityException extends \Exception implements EntityError
{
    public function __construct(
        public readonly EntityIdentifierInterface $entity,
    ) {
        parent::__construct(sprintf(
            'Cannot modify %s %s, entity is read-only',
            $entity->getEntityType(),
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

    public function getEntity(): EntityIdentifierInterface
    {
        return $this->entity;
    }

    public function serialize(): array
    {
        return [
            'class' => $this->getClass(),
            'entity' => [
                'id' => $this->entity->getId(),
                'type' => $this->entity->getEntityType(),
            ],
        ];
    }
}
