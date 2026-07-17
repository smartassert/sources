<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\SerializedSuiteInterface;
use App\Enum\SerializedSuite\State;
use Symfony\Contracts\EventDispatcher\Event;

class SerializedSuiteStateChangedEvent extends Event implements NotifiableEventInterface
{
    public const string REMOTE_EVENT_NAME = 'sources.serialized_suite.state_changed';

    public function __construct(
        public readonly SerializedSuiteInterface $serializedSuite,
        public readonly State $newState,
    ) {}

    public function getNotifyUrl(): ?string
    {
        $baseUrl = $this->serializedSuite->getNotifyUrl();
        if (null === $baseUrl) {
            return null;
        }

        return rtrim($baseUrl, '/') . '/' . $this->getRemoteEventName();
    }

    public function getRemoteEventName(): string
    {
        return self::REMOTE_EVENT_NAME;
    }

    public function getPayload(): array
    {
        return $this->serializedSuite->toArray();
    }
}
