<?php

declare(strict_types=1);

namespace App\Entity;

interface EntityIdentifierInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    /**
     * @return non-empty-string
     */
    public function getType(): string;

    /**
     * @return array{id: non-empty-string, type: non-empty-string}
     */
    public function serialize(): array;
}
