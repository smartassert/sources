<?php

declare(strict_types=1);

namespace App\Services\ServiceStatusInspector;

use App\Message\SerializeSuite;
use SmartAssert\ServiceStatusInspector\ComponentStatusInspectorInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageQueueInspector implements ComponentStatusInspectorInterface
{
    public const DEFAULT_IDENTIFIER = 'message_queue';

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly string $identifier = self::DEFAULT_IDENTIFIER,
    ) {
    }

    public function getStatus(): bool
    {
        $this->messageBus->dispatch(new SerializeSuite('invalid', []));

        return true;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
