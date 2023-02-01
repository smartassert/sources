<?php

declare(strict_types=1);

namespace App\Tests\Model;

use Symfony\Component\Uid\Ulid;

class UserId
{
    /**
     * @return non-empty-string
     */
    public static function create(): string
    {
        $userId = (string) new Ulid();

        if ('' === $userId) {
            throw new \RuntimeException('Empty user id generated');
        }

        return $userId;
    }
}
