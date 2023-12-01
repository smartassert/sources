<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer\ComponentFactory;

use App\FooResponse\ErrorInterface;
use App\FooResponse\ErrorSerializer\Component;
use App\FooResponse\ErrorSerializer\ComponentFactoryInterface;

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
