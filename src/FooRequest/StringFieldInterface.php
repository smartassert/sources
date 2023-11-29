<?php

declare(strict_types=1);

namespace App\FooRequest;

interface StringFieldInterface extends FieldInterface
{
    public function getValue(): string;

    public function getRequirements(): RequirementsInterface;
}
