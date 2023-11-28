<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\RequirementsInterface;
use App\FooResponse\SizeInterface;

readonly class Requirements implements RequirementsInterface
{
    /**
     * @param 'bool'|'float'|'int'|'string'                                              $dataType
     * @param RequirementsInterface::CAN_BE_EMPTY|RequirementsInterface::CANNOT_BE_EMPTY $canBeEmpty
     */
    public function __construct(
        private string $dataType,
        private ?SizeInterface $size,
        private bool $canBeEmpty,
    ) {
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function getSize(): ?SizeInterface
    {
        return $this->size;
    }

    public function canBeEmpty(): bool
    {
        return $this->canBeEmpty;
    }
}
