<?php

declare(strict_types=1);

namespace App\ErrorResponse\Serializer\ComponentFactory;

use App\ErrorResponse\BadRequestErrorInterface;
use App\ErrorResponse\ErrorInterface;
use App\ErrorResponse\Serializer\Component;
use App\ErrorResponse\Serializer\ComponentFactoryInterface;
use App\ErrorResponse\SizeInterface;
use App\RequestField\RequirementsInterface;
use App\RequestField\ScalarRequirementsInterface;

class RequirementsFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        if (!$error instanceof BadRequestErrorInterface) {
            return null;
        }

        $requirements = $error->getField()->getRequirements();
        if (!$requirements instanceof RequirementsInterface) {
            return null;
        }

        $data = [
            'data_type' => $requirements->getDataType(),
        ];

        if ($requirements instanceof ScalarRequirementsInterface) {
            $size = $requirements->getSize();
            if ($size instanceof SizeInterface) {
                $data['size'] = ['minimum' => $size->getMinimum(), 'maximum' => $size->getMaximum()];
            }
        }

        return new Component('requirements', $data);
    }
}
