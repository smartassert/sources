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
        $data = [
            'class' => $error->getClass(),
        ];

        $type = $error->getType();
        if (is_string($type)) {
            $data['type'] = $type;
        }

        if ($error instanceof BadRequestErrorInterface) {
            $field = $error->getField();

            $fieldData = [
                'name' => $field->getName(),
                'value' => $field->getValue(),
            ];

            if ($field instanceof CollectionFieldInterface) {
                $fieldData['position'] = $field->getErrorPosition();
            }

            $data['field'] = $fieldData;

            $fieldRequirements = $field->getRequirements();

            $renderRequirements =
                ($error instanceof RenderableErrorInterface && $error->renderRequirements())
                || !$error instanceof RenderableErrorInterface;

            if ($renderRequirements && $fieldRequirements instanceof RequirementsInterface) {
                $requirementsData = [
                    'data_type' => $fieldRequirements->getDataType(),
                ];

                if ($fieldRequirements instanceof ScalarRequirementsInterface) {
                    $fieldRequirementsSize = $fieldRequirements->getSize();
                    if ($fieldRequirementsSize instanceof SizeInterface) {
                        $requirementsData['size'] = [
                            'minimum' => $fieldRequirementsSize->getMinimum(),
                            'maximum' => $fieldRequirementsSize->getMaximum(),
                        ];
                    }
                }

                $data['requirements'] = $requirementsData;
            }
        }

        if ($error instanceof EntityErrorInterface) {
            $entity = $error->getEntity();

            $data['entity'] = [
                'type' => $entity->getEntityType()->value,
                'id' => $entity->getId(),
            ];
        }

        if ($error instanceof StorageLocationErrorInterface) {
            $data['location'] = $error->getLocation();
        }

        $statusCode = $error instanceof HasHttpErrorCodeInterface ? $error->getErrorCode() : 400;

        return new JsonResponse(
            $data,
            $statusCode
        );
    }
}
