<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer\ComponentFactory;

use App\FooResponse\ErrorInterface;
use App\FooResponse\ErrorSerializer\Component;
use App\FooResponse\ErrorSerializer\ComponentFactoryInterface;
use App\FooResponse\StorageLocationErrorInterface;

class StorageLocationFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        if (!$error instanceof StorageLocationErrorInterface) {
            return null;
        }

        return new Component('location', $error->getLocation());
    }
}
