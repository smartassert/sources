<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooRequest\FieldInterface;
use App\FooRequest\RequirementsInterface;

readonly class LabelField implements FieldInterface
{
    private Requirements $requirements;

    public function __construct(
        private string $value,
    ) {
        $this->requirements = new StringRequirements(
            new Size(1, 255),
            Requirements::CANNOT_BE_EMPTY
        );
    }

    public function getName(): string
    {
        return 'label';
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
