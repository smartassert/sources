<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Repository\SerializedSuiteRepository;

class NoSourceRepositoryCreatorExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    use HighPriorityTrait;

    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(\Throwable $exception): void
    {
        if (!$exception instanceof SerializeSuiteException) {
            return;
        }

        $handlerException = $exception->handlerException;
        $serializedSuite = $exception->serializedSuite;

        if (!$handlerException instanceof NoSourceRepositoryCreatorException) {
            return;
        }

        $serializedSuite->setPreparationFailed(
            FailureReason::UNSERIALIZABLE_SOURCE_TYPE,
            $handlerException->source->getType()->value
        );
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
