<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\ScalarRequirementsInterface;
use App\FooResponse\SizeInterface;

readonly class ScalarRequirements extends Requirements implements ScalarRequirementsInterface
{
    /**
     * @param 'bool'|'float'|'int'|'string' $dataType
     */
    public function __construct(
        string $dataType,
        private ?SizeInterface $size,
    ) {
        parent::__construct($dataType);
    }

    public function getSize(): ?SizeInterface
    {
        return $this->size;
    }
}
