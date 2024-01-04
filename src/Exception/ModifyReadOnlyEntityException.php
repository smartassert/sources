<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\EntityIdentifierInterface;
use App\Entity\IdentifiedEntityInterface as IdentifiedEntity;
use App\ErrorResponse\ModifyReadOnlyEntityErrorInterface;

class ModifyReadOnlyEntityException extends \Exception implements IdentifiedEntity, ModifyReadOnlyEntityErrorInterface
{
    public function __construct(
        public readonly IdentifiedEntity $entity,
    ) {
        parent::__construct(sprintf(
            'Cannot modify %s %s, entity is read-only',
            $entity->getIdentifier()->getType(),
            $entity->getIdentifier()->getId(),
        ));
    }

    public function getStatusCode(): int
    {
        return 405;
    }

    public function getClass(): string
    {
        return ModifyReadOnlyEntityErrorInterface::ERROR_CLASS;
    }

    public function getType(): null
    {
        return null;
    }

    public function getIdentifier(): EntityIdentifierInterface
    {
        return $this->entity->getIdentifier();
    }

    public function serialize(): array
    {
        return [
            'class' => $this->getClass(),
            'entity' => $this->entity->getIdentifier()->serialize(),
        ];
    }
}
