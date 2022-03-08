<?php

declare(strict_types=1);

namespace App\Services\ServiceStatusInspector;

use App\Message\Prepare;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageQueueInspector
{
    public const INVALID_MACHINE_ID = 'intentionally invalid';

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(): void
    {
        $this->messageBus->dispatch(new Prepare('invalid', []));
    }
}
