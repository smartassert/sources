<?php

declare(strict_types=1);

namespace App\Services\ErrorResponse;

use App\Exception\HasHttpErrorCodeInterface;
use App\FooResponse\ErrorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class Factory
{
    /**
     * @param iterable<ComponentFactoryInterface> $componentFactories
     */
    public function __construct(
        private iterable $componentFactories,
    ) {
    }

    public function create(ErrorInterface $error): JsonResponse
    {
        $components = [];
        foreach ($this->componentFactories as $componentFactory) {
            if ($componentFactory instanceof ComponentFactoryInterface) {
                $component = $componentFactory->create($error);
                if ($component instanceof Component) {
                    $components[] = $component;
                }
            }
        }

        $data = [];
        foreach ($components as $component) {
            $data = array_merge($data, $component->toArray());
        }

        $statusCode = $error instanceof HasHttpErrorCodeInterface ? $error->getErrorCode() : 400;

        return new JsonResponse($data, $statusCode);
    }
}
