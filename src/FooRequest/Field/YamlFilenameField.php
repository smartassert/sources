<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\RequirementsInterface;
use App\FooRequest\StringFieldInterface;
use SmartAssert\YamlFile\Filename as YamlFilename;

readonly class YamlFilenameField implements StringFieldInterface
{
    private RequirementsInterface $requirements;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private string $name,
        private YamlFilename $value,
    ) {
        $this->requirements = new Requirements('yaml_filename', RequirementsInterface::CANNOT_BE_EMPTY);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return (string) $this->value;
    }

    public function getFilename(): YamlFilename
    {
        return $this->value;
    }

    public function getRequirements(): RequirementsInterface
    {
        return $this->requirements;
    }
}
