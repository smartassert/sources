<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;

class ClassFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        return new Component('class', $error->getClass());
    }
}
