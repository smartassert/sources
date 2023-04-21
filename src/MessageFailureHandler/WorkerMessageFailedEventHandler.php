<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Message\SerializeSuite;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class WorkerMessageFailedEventHandler implements EventSubscriberInterface
{
    /**
     * @var ExceptionHandlerInterface[]
     */
    private readonly array $exceptionHandlers;

    /**
     * @param array<mixed> $exceptionHandlers
     */
    public function __construct(
        array $exceptionHandlers,
    ) {
        $handlers = [];

        foreach ($exceptionHandlers as $exceptionHandler) {
            if ($exceptionHandler instanceof ExceptionHandlerInterface) {
                $handlers[] = $exceptionHandler;
            }
        }

        $this->exceptionHandlers = $handlers;
    }

    /**
     * @return array<string, array<int, array<int, int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => [
                ['handleWorkerMessageFailedEvent', 0],
            ],
        ];
    }

    public function handleWorkerMessageFailedEvent(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof SerializeSuite) {
            return;
        }

        if ($event->willRetry()) {
            return;
        }

        $handlerFailedException = $event->getThrowable();
        if (!$handlerFailedException instanceof HandlerFailedException) {
            return;
        }

        foreach ($this->exceptionHandlers as $exceptionHandler) {
            $exceptionHandler->handle($handlerFailedException->getNestedExceptions());
        }
    }
}
