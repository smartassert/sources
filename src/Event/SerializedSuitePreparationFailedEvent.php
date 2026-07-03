<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\SerializedSuiteInterface;
use App\Enum\SerializedSuite\FailureReason;
use Symfony\Contracts\EventDispatcher\Event;

class SerializedSuitePreparationFailedEvent extends Event
{
    public function __construct(
        public readonly SerializedSuiteInterface $serializedSuite,
        public readonly FailureReason $failureReason,
        public readonly string $failureMessage,
    ) {}
}
