<?php

declare(strict_types=1);

namespace App\FooRequest;

interface FieldInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @return scalar
     */
    public function getValue(): bool|float|int|string;

    public function getRequirements(): RequirementsInterface;
}
