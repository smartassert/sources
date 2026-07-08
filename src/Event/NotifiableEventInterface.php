<?php

declare(strict_types=1);

namespace App\Event;

interface NotifiableEventInterface
{
    /**
     * @return non-empty-string
     */
    public function getRemoteEventName(): string;
}
