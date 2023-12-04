<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer;

use App\FooResponse\ErrorInterface;

interface ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component;
}
