<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\EntityIdentifierInterface;
use App\Entity\IdentifiedEntityInterface;
use App\ErrorResponse\EntityErrorInterface;

class ModifyReadOnlyEntityException extends \Exception implements IdentifiedEntityInterface, EntityErrorInterface
{
    public function __construct(
        public readonly IdentifiedEntityInterface $entity,
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
        return 'modify_read_only';
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
