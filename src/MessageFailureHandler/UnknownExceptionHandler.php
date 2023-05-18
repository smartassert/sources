<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Repository\SerializedSuiteRepository;

class UnknownExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializeSuiteException $exception): void
    {
        $handlerException = $exception->handlerException;
        $serializedSuite = $exception->serializedSuite;

        $serializedSuite->setPreparationFailed(FailureReason::UNKNOWN, $handlerException->getMessage());
        $this->serializedSuiteRepository->save($serializedSuite);
    }

    public static function getDefaultPriority(): int
    {
        return -100;
    }
}
