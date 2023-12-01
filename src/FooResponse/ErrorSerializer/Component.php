<?php

declare(strict_types=1);

namespace App\FooResponse\ErrorSerializer;

readonly class Component
{
    public function __construct(
        private string $key,
        private mixed $data,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        if (is_array($this->data)) {
            $renderedData = [];

            foreach ($this->data as $key => $value) {
                if ($value instanceof Component) {
                    $renderedData = array_merge($renderedData, $value->toArray());
                } else {
                    $renderedData[$key] = $value;
                }
            }
        } else {
            $renderedData = $this->data;
        }

        return [
            $this->key => $renderedData,
        ];
    }
}
