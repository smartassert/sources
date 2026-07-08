<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\SerializedSuiteInterface;
use App\Enum\SerializedSuite\State;
use Symfony\Contracts\EventDispatcher\Event;

interface NotifiableEventInterface
{
    /**
     * @return non-empty-string
     */
    public function getRemoteEventName(): string;
}
