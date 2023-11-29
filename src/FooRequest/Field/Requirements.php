<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\RequirementsInterface;

readonly class Requirements implements RequirementsInterface
{
    /**
     * @param non-empty-string                                                           $dataType
     * @param RequirementsInterface::CAN_BE_EMPTY|RequirementsInterface::CANNOT_BE_EMPTY $canBeEmpty
     */
    public function __construct(
        private string $dataType,
        private bool $canBeEmpty,
    ) {
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function canBeEmpty(): bool
    {
        return $this->canBeEmpty;
    }
}
