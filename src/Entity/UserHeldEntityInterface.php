<?php

declare(strict_types=1);

namespace App\Entity;

interface UserHeldEntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getUserId(): string;
}
