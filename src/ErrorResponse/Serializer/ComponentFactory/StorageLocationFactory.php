<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;
use App\ErrorResponse\StorageLocationErrorInterface;

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
