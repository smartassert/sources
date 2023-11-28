<?php

declare(strict_types=1);

namespace App\FooResponse;

interface RequirementsInterface
{
    /**
     * @return null|'bool'|'float'|'int'|'string'
     */
    public function getDataType(): ?string;

    public function getSize(): SizeInterface;
}
