<?php

declare(strict_types=1);

namespace App\FooResponse;

interface FieldInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @return scalar
     */
    public function getValue(): bool|float|int|string;

    /**
     * @return 'bool'|'float'|'int'|'string'
     */
    public function getDataType(): ?string;
}
