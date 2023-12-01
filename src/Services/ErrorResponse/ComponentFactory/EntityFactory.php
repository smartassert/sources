<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse\ComponentFactory;

use App\FooResponse\EntityErrorInterface;
use App\FooResponse\ErrorInterface;
use App\Services\ErrorResponse\Component;
use App\Services\ErrorResponse\ComponentFactoryInterface;

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
