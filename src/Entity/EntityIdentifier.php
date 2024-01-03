<?php

declare(strict_types=1);

namespace App\Entity;

readonly class EntityIdentifier implements EntityIdentifierInterface
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $type
     */
    public function __construct(
        private string $id,
        private string $type,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
