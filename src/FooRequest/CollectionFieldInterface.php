<?php

declare(strict_types=1);

namespace App\FooRequest;

interface CollectionFieldInterface extends FieldInterface
{
    /**
     * @return array<0|positive-int, string>
     */
    public function getValue(): array;

    /**
     * @return ?positive-int
     */
    public function getErrorPosition(): ?int;
}
