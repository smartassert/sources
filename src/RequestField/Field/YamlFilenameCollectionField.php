<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\RequestField\FieldInterface;
use App\RequestField\RequirementsInterface;

readonly class YamlFilenameCollectionField implements FieldInterface
{
    private RequirementsInterface $requirements;

    /**
     * @param non-empty-string $name
     * @param string[]         $value
     */
    public function __construct(
        private string $name,
        private array $value,
        private ?int $errorPosition = null,
    ) {
        $this->requirements = new Requirements('yaml_filename_collection');
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getValue(): array
    {
        return $this->value;
    }

    public function getRequirements(): RequirementsInterface
    {
        return $this->requirements;
    }

    public function getErrorPosition(): ?int
    {
        return $this->errorPosition;
    }

    public function withErrorPosition(int $position): FieldInterface
    {
        return new YamlFilenameCollectionField($this->name, $this->value, $position);
    }
}
