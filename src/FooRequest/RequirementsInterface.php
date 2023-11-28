<?php

declare(strict_types=1);

namespace App\FooRequest;

use App\FooResponse\SizeInterface;

interface RequirementsInterface
{
    public const CAN_BE_EMPTY = true;
    public const CANNOT_BE_EMPTY = false;

    /**
     * @return 'bool'|'float'|'int'|'string'
     */
    public function getDataType(): string;

    public function getSize(): ?SizeInterface;

    public function canBeEmpty(): bool;
}
