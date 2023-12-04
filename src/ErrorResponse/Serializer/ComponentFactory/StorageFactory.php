<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;
use App\ErrorResponse\StorageErrorInterface;

class StorageFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        if (!$error instanceof StorageErrorInterface) {
            return null;
        }

        return new Component(data: [
            'location' => $error->getLocation(),
            'object_type' => $error->getObjectType(),
            'context' => $error->getContext(),
        ]);
    }
}
