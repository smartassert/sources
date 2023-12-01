<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse\ComponentFactory;

use App\FooResponse\ErrorInterface;
use App\Services\ErrorResponse\Component;
use App\Services\ErrorResponse\ComponentFactoryInterface;

class ClassFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        return new Component('class', $error->getClass());
    }
}
