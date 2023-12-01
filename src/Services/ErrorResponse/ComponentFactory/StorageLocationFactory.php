<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse\ComponentFactory;

use App\FooResponse\ErrorInterface;
use App\FooResponse\StorageLocationErrorInterface;
use App\Services\ErrorResponse\Component;
use App\Services\ErrorResponse\ComponentFactoryInterface;

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
