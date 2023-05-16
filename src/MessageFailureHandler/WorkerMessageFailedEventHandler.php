<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Message\SerializeSuite;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class WorkerMessageFailedEventHandler implements EventSubscriberInterface
{
    public const STATE_SUCCESS = 0;
    public const STATE_INCORRECT_MESSAGE_TYPE = 1;
    public const STATE_EVENT_WILL_RETRY = 2;
    public const STATE_EVENT_EXCEPTION_INCORRECT_TYPE = 3;

    /**
     * @var ExceptionCollectionHandlerInterface[]
     */
    private readonly array $handlers;

    /**
     * @param array<mixed> $exceptionCollectionHandlers
     */
    public function __construct(
        array $exceptionCollectionHandlers,
    ) {
        $handlers = [];

        foreach ($exceptionCollectionHandlers as $exceptionCollectionHandler) {
            if ($exceptionCollectionHandler instanceof ExceptionCollectionHandlerInterface) {
                $handlers[] = $exceptionCollectionHandler;
            }
        }

        $this->handlers = $handlers;
    }

    /**
     * @return array<string, array<int, array<int, int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => [
                ['handle', 0],
            ],
        ];
    }

    public function handle(WorkerMessageFailedEvent $event): int
    {
        $message = $event->getEnvelope()->getMessage();
        if ($event->willRetry()) {
            return self::STATE_EVENT_WILL_RETRY;
        }

        if (!$message instanceof SerializeSuite) {
            return self::STATE_INCORRECT_MESSAGE_TYPE;
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
