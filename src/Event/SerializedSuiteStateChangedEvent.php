<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\SerializedSuiteInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SerializedSuiteStateChangedEvent extends Event
{
    public function __construct(
        public readonly SerializedSuiteInterface $serializedSuite,
    ) {}
}
