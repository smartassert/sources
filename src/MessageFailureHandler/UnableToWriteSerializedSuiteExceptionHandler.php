<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\UnableToWriteSerializedSuiteException;
use App\Repository\SerializedSuiteRepository;

class UnableToWriteSerializedSuiteExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    use HighPriorityTrait;

    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializeSuiteException $exception): void
    {
        $handlerException = $exception->handlerException;
        $serializedSuite = $exception->serializedSuite;

        if (!$handlerException instanceof UnableToWriteSerializedSuiteException) {
            return;
        }

        $serializedSuite->setPreparationFailed(FailureReason::UNABLE_TO_WRITE_TO_TARGET, $handlerException->path);
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
