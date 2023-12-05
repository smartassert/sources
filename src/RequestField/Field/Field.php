<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\RequestField\FieldInterface;
use App\RequestField\RequirementsInterface;

readonly class Field implements FieldInterface
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private string $name,
        private string $value,
        private ?RequirementsInterface $requirements = null,
        private ?int $errorPosition = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getRequirements(): ?RequirementsInterface
    {
        return $this->requirements;
    }

    public function getErrorPosition(): ?int
    {
        return $this->errorPosition;
    }

    public function withErrorPosition(int $position): FieldInterface
    {
        return new Field($this->name, $this->value, $this->requirements, $position);
    }
}
