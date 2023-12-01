<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse\ComponentFactory;

use App\FooResponse\ErrorInterface;
use App\Services\ErrorResponse\Component;
use App\Services\ErrorResponse\ComponentFactoryInterface;

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
