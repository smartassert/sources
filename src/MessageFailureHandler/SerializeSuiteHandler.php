<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;
use App\Repository\SerializedSuiteRepository;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;

class SerializeSuiteHandler implements ExceptionHandlerInterface
{
    use HighPriorityTrait;

    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(\Throwable $throwable): void
    {
        if (
            !$throwable instanceof SerializeSuiteException
            || null === $throwable->failureReason
            || null == $throwable->failureMessage
        ) {
            return;
        }

        $throwable->serializedSuite->setPreparationFailed($throwable->failureReason, $throwable->failureMessage);
        $this->serializedSuiteRepository->save($throwable->serializedSuite);
    }
}
