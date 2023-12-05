<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\BadRequestErrorInterface;
use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;
use App\RequestField\RequirementsInterface;
use App\RequestField\SizeInterface;

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

        $errorPosition = $field->getErrorPosition();
        if (is_int($errorPosition)) {
            $data['position'] = $errorPosition;
        }

        $requirements = $error->getField()->getRequirements();
        if ($requirements instanceof RequirementsInterface) {
            $requirementsData = [];

            $requirementsData = [
                'data_type' => $requirements->getDataType(),
            ];

            $size = $requirements->getSize();
            if ($size instanceof SizeInterface) {
                $requirementsData['size'] = ['minimum' => $size->getMinimum(), 'maximum' => $size->getMaximum()];
            }

            $data['requirements'] = $requirementsData;
        }

        return new Component('field', $data);
    }
}
