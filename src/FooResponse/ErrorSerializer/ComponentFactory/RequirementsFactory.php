<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer\ComponentFactory;

use App\FooRequest\RequirementsInterface;
use App\FooRequest\ScalarRequirementsInterface;
use App\FooResponse\BadRequestErrorInterface;
use App\FooResponse\ErrorInterface;
use App\FooResponse\ErrorSerializer\Component;
use App\FooResponse\ErrorSerializer\ComponentFactoryInterface;
use App\FooResponse\SizeInterface;

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
