<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Repository\SerializedSuiteRepository;

class UnknownExceptionHandler implements SerializeSuiteSubExceptionHandlerInterface
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        $serializedSuite->setPreparationFailed(FailureReason::UNKNOWN, $exception->getMessage());
        $this->serializedSuiteRepository->save($serializedSuite);
    }

    public static function getDefaultPriority(): int
    {
        return -100;
    }
}
