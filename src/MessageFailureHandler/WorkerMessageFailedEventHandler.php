<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class WorkerMessageFailedEventHandler
{
    /**
     * @param iterable<ExceptionCollectionHandlerInterface> $handlers
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

        $handlerFailedException = $event->getThrowable();
        if (!$handlerFailedException instanceof HandlerFailedException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($handlerFailedException->getNestedExceptions());
        }
    }
}
