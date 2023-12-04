<?php

declare(strict_types=1);

namespace App\ErrorResponse\ErrorSerializer\ComponentFactory;

use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\ErrorSerializer\Component;
use App\ErrorResponse\ErrorSerializer\ComponentFactoryInterface;

class ClassFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        return new Component('class', $error->getClass());
    }
}
