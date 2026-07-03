<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Event\SerializedSuitePreparationFailedEvent;
use App\Exception\MessageHandler\SerializeSuiteException;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class SerializeSuiteHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(Envelope $envelope, \Throwable $throwable): void
    {
        if (!$throwable instanceof SerializeSuiteException) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new SerializedSuitePreparationFailedEvent(
                $throwable->serializedSuite,
                $throwable->failureReason,
                $throwable->failureMessage,
            )
        );
    }
}
