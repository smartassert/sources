<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse;

use App\Exception\HasHttpErrorCodeInterface;
use App\FooRequest\CollectionFieldInterface;
use App\FooRequest\RequirementsInterface;
use App\FooRequest\ScalarRequirementsInterface;
use App\FooResponse\BadRequestErrorInterface;
use App\FooResponse\EntityErrorInterface;
use App\FooResponse\ErrorInterface;
use App\FooResponse\RenderableErrorInterface;
use App\FooResponse\SizeInterface;
use App\FooResponse\StorageLocationErrorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class Factory
{
    public function create(ErrorInterface $error): JsonResponse
    {
        $components = [
            new Component('class', $error->getClass()),
        ];

        $type = $error->getType();
        if (is_string($type)) {
            $components[] = new Component('type', $error->getType());
        }

        if ($error instanceof StorageLocationErrorInterface) {
            $components[] = new Component('location', $error->getLocation());
        }

        if ($error instanceof EntityErrorInterface) {
            $components[] = new Component('entity', [
                'type' => $error->getEntity()->getEntityType()->value,
                'id' => $error->getEntity()->getId(),
            ]);
        }

        if ($error instanceof BadRequestErrorInterface) {
            $field = $error->getField();

            $fieldComponents = [
                new Component('name', $field->getName()),
                new Component('value', $field->getValue()),
            ];

            if ($field instanceof CollectionFieldInterface) {
                $fieldComponents[] = new Component('position', $field->getErrorPosition());
            }

            $components[] = new Component('field', $fieldComponents);

            $fieldRequirements = $field->getRequirements();

            $renderRequirements =
                ($error instanceof RenderableErrorInterface && $error->renderRequirements())
                || !$error instanceof RenderableErrorInterface;

            if ($renderRequirements && $fieldRequirements instanceof RequirementsInterface) {
                $requirementsComponents = [
                    new Component('data_type', $fieldRequirements->getDataType()),
                ];

                if ($fieldRequirements instanceof ScalarRequirementsInterface) {
                    $fieldRequirementsSize = $fieldRequirements->getSize();
                    if ($fieldRequirementsSize instanceof SizeInterface) {
                        $requirementsComponents[] = new Component('size', [
                            new Component('minimum', $fieldRequirementsSize->getMinimum()),
                            new Component('maximum', $fieldRequirementsSize->getMaximum()),
                        ]);
                    }
                }

                $components[] = new Component('requirements', $requirementsComponents);
            }
        }

        $data = [];
        foreach ($components as $component) {
            $data = array_merge($data, $component->toArray());
        }

        $statusCode = $error instanceof HasHttpErrorCodeInterface ? $error->getErrorCode() : 400;

        return new JsonResponse(
            $data,
            $statusCode
        );
    }
}
