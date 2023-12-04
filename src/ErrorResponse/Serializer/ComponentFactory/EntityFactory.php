<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\EntityErrorInterface;
use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;

class EntityFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        if (!$error instanceof EntityErrorInterface) {
            return null;
        }

        return new Component('entity', [
            'type' => $error->getEntity()->getEntityType()->value,
            'id' => $error->getEntity()->getId(),
        ]);
    }
}
