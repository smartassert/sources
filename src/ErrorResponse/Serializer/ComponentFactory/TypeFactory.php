<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;

class TypeFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        $type = $error->getType();
        if (!is_string($type)) {
            return null;
        }

        return new Component('type', $error->getType());
    }
}
