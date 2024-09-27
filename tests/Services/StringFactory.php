<?php

declare(strict_types=1);

namespace App\Tests\Services;

class StringFactory
{
    /**
     * @return non-empty-string
     */
    public static function createRandom(): string
    {
        return md5((string) rand());
    }
}
