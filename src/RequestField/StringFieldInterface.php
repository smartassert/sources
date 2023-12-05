<?php

declare(strict_types=1);

namespace App\RequestField;

interface StringFieldInterface extends FieldInterface
{
    public function getValue(): string;
}
