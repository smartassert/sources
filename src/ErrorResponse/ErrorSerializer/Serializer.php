<?php

declare(strict_types=1);

namespace App\ErrorResponse\ErrorSerializer;

use App\ErrorResponse\ErrorInterface;

readonly class Serializer
{
    /**
     * @param iterable<ComponentFactoryInterface> $componentFactories
     */
    public function __construct(
        private iterable $componentFactories,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function create(ErrorInterface $error): array
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
            $data[$component->key] = $component->data;
        }

        return $data;
    }
}
