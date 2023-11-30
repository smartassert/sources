<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EntityType;

interface IdentifyingEntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    public function getEntityType(): EntityType;
}
