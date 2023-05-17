<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Repository\SerializedSuiteRepository;

class NoSourceRepositoryCreatorExceptionHandler implements SerializeSuiteSubExceptionHandlerInterface
{
    use HighPriorityTrait;

    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        if (!$exception instanceof NoSourceRepositoryCreatorException) {
            return;
        }

        $serializedSuite->setPreparationFailed(
            FailureReason::UNSERIALIZABLE_SOURCE_TYPE,
            $exception->source->getType()->value
        );
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
