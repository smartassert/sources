<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\UnableToWriteSerializedSuiteException;
use App\Repository\SerializedSuiteRepository;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;

class UnableToWriteSerializedSuiteExceptionHandler implements ExceptionHandlerInterface
{
    use HighPriorityTrait;

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

        if (!$throwable instanceof UnableToWriteSerializedSuiteException) {
            return;
        }

        $serializedSuite->setPreparationFailed(FailureReason::UNABLE_TO_WRITE_TO_TARGET, $throwable->path);
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
