<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Uid\Ulid;

class EntityId
{
    public static function create(): string
    {
        return (string) new Ulid();
    }
}
