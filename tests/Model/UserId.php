<?php

declare(strict_types=1);

namespace App\Tests\Model;

use Symfony\Component\Uid\Ulid;

class UserId
{
    public static function create(): string
    {
        return (string) new Ulid();
    }
}
