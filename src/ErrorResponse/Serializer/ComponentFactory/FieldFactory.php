<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\BadRequestErrorInterface;
use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;

class FieldFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        if (!$error instanceof BadRequestErrorInterface) {
            return null;
        }

        return new Component('field', $error->getField()->jsonSerialize());
    }
}
