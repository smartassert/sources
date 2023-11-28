<?php

declare(strict_types=1);

namespace App\FooResponse;

interface ErrorInterface
{
    /**
     * @return non-empty-string
     */
    public function getClass(): string;

    public function getField(): FieldInterface;

    /**
     * @return ?non-empty-string
     */
    public function getType(): ?string;

    public function getRequirements(): RequirementsInterface;
}
