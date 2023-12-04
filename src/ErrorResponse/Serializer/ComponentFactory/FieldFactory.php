<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\BadRequestErrorInterface;
use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;
use App\RequestField\CollectionFieldInterface;

class FieldFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        if (!$error instanceof BadRequestErrorInterface) {
            return null;
        }

        $field = $error->getField();
        $data = [
            'name' => $field->getName(),
            'value' => $field->getValue(),
        ];

        if ($field instanceof CollectionFieldInterface) {
            $data['position'] = $field->getErrorPosition();
        }

        return new Component('field', $data);
    }
}
