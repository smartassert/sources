<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse;

use App\FooResponse\ErrorInterface;

interface ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component;
}
