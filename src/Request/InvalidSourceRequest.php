<?php

declare(strict_types=1);

namespace App\Request;

class InvalidSourceRequest implements SourceRequestInterface
{
    /**
     * @param string[] $missingRequiredFields
     */
    public function __construct(
        private string $sourceType,
        private array $missingRequiredFields
    ) {
    }

    public function getRequiredFields(): array
    {
        return [];
    }

    public function getFields(): array
    {
        return [];
    }

    public function getParameter(string $name): string
    {
        return '';
    }

    public function isValid(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return $this->sourceType;
    }

    public function getMissingRequiredFields(): array
    {
        return $this->missingRequiredFields;
    }
}
