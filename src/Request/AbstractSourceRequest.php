<?php

declare(strict_types=1);

namespace App\Request;

abstract class AbstractSourceRequest implements SourceRequestInterface
{
    public const KEY_POST_TYPE = 'type';

    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private array $parameters
    ) {
    }

    public function getRequiredFields(): array
    {
        return [
            self::KEY_POST_TYPE,
        ];
    }

    public function getFields(): array
    {
        return $this->getRequiredFields();
    }

    public function getParameter(string $name): string
    {
        return $this->parameters[$name] ?? '';
    }

    public function getMissingRequiredFields(): array
    {
        $missingRequiredFields = [];

        foreach ($this->getRequiredFields() as $name) {
            if ('' === ($this->parameters[$name] ?? '')) {
                $missingRequiredFields[] = $name;
            }
        }

        return $missingRequiredFields;
    }

    public function isValid(): bool
    {
        return [] === $this->getMissingRequiredFields();
    }
}
