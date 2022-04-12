<?php

declare(strict_types=1);

namespace App\Services\ServiceStatusInspector;

use App\Message\Prepare;
use SmartAssert\ServiceStatusInspector\ComponentInspectorInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageQueueInspector implements ComponentInspectorInterface
{
    public const INVALID_MACHINE_ID = 'intentionally invalid';
    public const DEFAULT_IDENTIFIER = 'message_queue';

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly string $identifier = self::DEFAULT_IDENTIFIER,
    ) {
    }

    public function isAvailable(): bool
    {
        $this->messageBus->dispatch(new Prepare('invalid', []));

        return true;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
