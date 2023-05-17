<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class WorkerMessageFailedEventHandler
{
    /**
     * @param iterable<ExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($event->getThrowable());
        }
    }
}
