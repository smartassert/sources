<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\RequirementsInterface;
use App\FooRequest\StringFieldInterface;

readonly class StringField implements StringFieldInterface
{
    private Requirements $requirements;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private string $name,
        private string $value,
        int $minimumLength,
        int $maximumLength,
    ) {
        $this->requirements = new StringRequirements(new Size($minimumLength, $maximumLength));
    }

    public function __toString(): string
    {
        return $this->getValue();
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
}
