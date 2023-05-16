<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class WorkerMessageFailedEventHandler
{
    public const STATE_SUCCESS = 0;
    public const STATE_EVENT_WILL_RETRY = 1;
    public const STATE_EVENT_EXCEPTION_INCORRECT_TYPE = 2;

    /**
     * @param iterable<ExceptionCollectionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function __invoke(WorkerMessageFailedEvent $event): int
    {
        if ($event->willRetry()) {
            return self::STATE_EVENT_WILL_RETRY;
        }

        $handlerFailedException = $event->getThrowable();
        if (!$handlerFailedException instanceof HandlerFailedException) {
            return self::STATE_EVENT_EXCEPTION_INCORRECT_TYPE;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($handlerFailedException->getNestedExceptions());
        }

        return self::STATE_SUCCESS;
    }
}
