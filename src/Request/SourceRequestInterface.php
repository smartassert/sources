<?php

declare(strict_types=1);

namespace App\Request;

interface SourceRequestInterface
{
    /**
     * @return string[]
     */
    public function getRequiredFields(): array;

    /**
     * @return string[]
     */
    public function getFields(): array;

    public function getParameter(string $name): string;

    public function isValid(): bool;

    /**
     * @return string[]
     */
    public function getMissingRequiredFields(): array;

    public function getType(): string;
}
