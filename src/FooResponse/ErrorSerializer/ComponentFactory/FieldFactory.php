<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer\ComponentFactory;

use App\FooResponse\BadRequestErrorInterface;
use App\FooResponse\ErrorInterface;
use App\FooResponse\ErrorSerializer\Component;
use App\FooResponse\ErrorSerializer\ComponentFactoryInterface;
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
