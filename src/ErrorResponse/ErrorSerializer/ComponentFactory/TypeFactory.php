<?php

declare(strict_types=1);

namespace App\ErrorResponse\ErrorSerializer\ComponentFactory;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\ErrorSerializer\Component;
use App\ErrorResponse\ErrorSerializer\ComponentFactoryInterface;

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
