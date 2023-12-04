<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer;

use App\ErrorResponse\ErrorInterface;

interface ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component;
}
