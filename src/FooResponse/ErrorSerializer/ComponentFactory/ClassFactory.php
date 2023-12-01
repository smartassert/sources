<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer\ComponentFactory;

use App\FooResponse\ErrorInterface;
use App\FooResponse\ErrorSerializer\Component;
use App\FooResponse\ErrorSerializer\ComponentFactoryInterface;

class ClassFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        return new Component('class', $error->getClass());
    }
}
