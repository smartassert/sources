<?php

declare(strict_types=1);

namespace App\RequestField;

interface FieldInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @return array<scalar>|scalar
     */
    public function getValue(): mixed;

    public function getRequirements(): ?RequirementsInterface;

    public function getErrorPosition(): ?int;

    public function withErrorPosition(int $position): FieldInterface;
}
