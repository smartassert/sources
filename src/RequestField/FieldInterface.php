<?php

declare(strict_types=1);

namespace App\RequestField;

interface FieldInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    public function getValue(): mixed;

    public function getRequirements(): ?RequirementsInterface;
}
