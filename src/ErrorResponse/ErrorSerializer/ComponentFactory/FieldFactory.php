<?php

declare(strict_types=1);

namespace App\ErrorResponse\ErrorSerializer\ComponentFactory;

use App\ErrorResponse\BadRequestErrorInterface;
use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\ErrorSerializer\Component;
use App\ErrorResponse\ErrorSerializer\ComponentFactoryInterface;
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
