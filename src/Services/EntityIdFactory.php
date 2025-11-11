<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Uid\Ulid;

class EntityIdFactory
{
    /**
     * @return non-empty-string
     */
    public function create(): string
    {
        return (string) new Ulid();
    }
}
