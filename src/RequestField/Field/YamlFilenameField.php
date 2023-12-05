<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\RequestField\FieldInterface;
use App\RequestField\RequirementsInterface;
use SmartAssert\YamlFile\Filename as YamlFilename;

readonly class YamlFilenameField implements FieldInterface
{
    private RequirementsInterface $requirements;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private string $name,
        private string $value,
    ) {
        $this->requirements = new Requirements('yaml_filename');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getRequirements(): RequirementsInterface
    {
        return $this->requirements;
    }

    public function getErrorPosition(): ?int
    {
        return null;
    }

    public function withErrorPosition(int $position): FieldInterface
    {
        return $this;
    }
}
