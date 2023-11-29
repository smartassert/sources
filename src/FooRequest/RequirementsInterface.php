<?php

declare(strict_types=1);

namespace App\FooRequest;

interface RequirementsInterface
{
    public const CAN_BE_EMPTY = true;
    public const CANNOT_BE_EMPTY = false;

    /**
     * @return non-empty-string
     */
    public function getDataType(): string;

    public function canBeEmpty(): bool;
}
