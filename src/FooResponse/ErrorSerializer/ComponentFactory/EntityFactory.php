<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer\ComponentFactory;

use App\FooResponse\EntityErrorInterface;
use App\FooResponse\ErrorInterface;
use App\FooResponse\ErrorSerializer\Component;
use App\FooResponse\ErrorSerializer\ComponentFactoryInterface;

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
