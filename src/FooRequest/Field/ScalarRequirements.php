<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\RequirementsInterface;
use App\FooRequest\ScalarRequirementsInterface;
use App\FooResponse\SizeInterface;

readonly class ScalarRequirements extends Requirements implements ScalarRequirementsInterface
{
    /**
     * @param 'bool'|'float'|'int'|'string'                                              $dataType
     * @param RequirementsInterface::CAN_BE_EMPTY|RequirementsInterface::CANNOT_BE_EMPTY $canBeEmpty
     */
    public function __construct(
        string $dataType,
        private ?SizeInterface $size,
        bool $canBeEmpty,
    ) {
        parent::__construct($dataType, $canBeEmpty);
    }

    public function getSize(): ?SizeInterface
    {
        return $this->size;
    }
}
