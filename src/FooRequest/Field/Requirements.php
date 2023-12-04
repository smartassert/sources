<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\RequirementsInterface;

readonly class Requirements implements RequirementsInterface
{
    /**
     * @param non-empty-string $dataType
     */
    public function __construct(
        private string $dataType,
    ) {
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }
}
