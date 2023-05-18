<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Repository\SerializedSuiteRepository;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;

class UnknownExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(\Throwable $throwable): void
    {
        if (!$throwable instanceof SerializeSuiteException) {
            return;
        }

        $serializedSuite = $throwable->serializedSuite;
        $throwable = $throwable->handlerException;

        $serializedSuite->setPreparationFailed(FailureReason::UNKNOWN, $throwable->getMessage());
        $this->serializedSuiteRepository->save($serializedSuite);
    }

    public static function getDefaultPriority(): int
    {
        return -100;
    }
}
