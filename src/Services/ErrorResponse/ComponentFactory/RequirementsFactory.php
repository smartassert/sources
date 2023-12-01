<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse\ComponentFactory;

use App\FooRequest\RequirementsInterface;
use App\FooRequest\ScalarRequirementsInterface;
use App\FooResponse\BadRequestErrorInterface;
use App\FooResponse\ErrorInterface;
use App\FooResponse\RenderableErrorInterface;
use App\FooResponse\SizeInterface;
use App\Services\ErrorResponse\Component;
use App\Services\ErrorResponse\ComponentFactoryInterface;

class RequirementsFactory implements ComponentFactoryInterface
{
    public function create(ErrorInterface $error): ?Component
    {
        if (!$error instanceof BadRequestErrorInterface) {
            return null;
        }

        if ($error instanceof RenderableErrorInterface && !$error->renderRequirements()) {
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
