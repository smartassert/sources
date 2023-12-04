<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\RequestField\CollectionFieldInterface;
use App\RequestField\RequirementsInterface;

class YamlFilenameCollectionField implements CollectionFieldInterface
{
    private RequirementsInterface $requirements;

    /**
     * @var ?positive-int
     */
    private ?int $errorPosition = null;

    /**
     * @param non-empty-string $name
     * @param string[]         $value
     */
    public function __construct(
        private readonly string $name,
        private readonly array $value,
    ) {
        $this->requirements = new Requirements('yaml_filename_collection');
    }

    public function getName(): string
    {
        return $this->name;
    }

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

    /**
     * @param positive-int $position
     */
    public function setErrorPosition(int $position): void
    {
        $this->errorPosition = $position;
    }
}
