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
}
