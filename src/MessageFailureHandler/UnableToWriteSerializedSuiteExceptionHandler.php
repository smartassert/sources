<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\UnableToWriteSerializedSuiteException;
use App\Repository\SerializedSuiteRepository;

class UnableToWriteSerializedSuiteExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    use HighPriorityTrait;

    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        if (!$exception instanceof UnableToWriteSerializedSuiteException) {
            return;
        }

        $serializedSuite->setPreparationFailed(FailureReason::UNABLE_TO_WRITE_TO_TARGET, $exception->path);
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
