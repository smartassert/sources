<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Repository\SerializedSuiteRepository;

class SourceRepositoryReaderNotFoundExceptionHandler implements SerializeSuiteSubExceptionHandlerInterface
{
    use HighPriorityTrait;

    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        if (!$exception instanceof SourceRepositoryReaderNotFoundException) {
            return;
        }

        $serializedSuite->setPreparationFailed(
            FailureReason::UNABLE_TO_READ_FROM_SOURCE_REPOSITORY,
            $exception->source->getRepositoryPath()
        );
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
