<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

trait HighPriorityTrait
{
    public static function getDefaultPriority(): int
    {
        return 100;
    }
}
