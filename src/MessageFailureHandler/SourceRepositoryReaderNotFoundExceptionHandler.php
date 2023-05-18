<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Repository\SerializedSuiteRepository;

class SourceRepositoryReaderNotFoundExceptionHandler implements FooInterface
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

        if (!$handlerException instanceof SourceRepositoryReaderNotFoundException) {
            return;
        }

        $serializedSuite->setPreparationFailed(
            FailureReason::UNABLE_TO_READ_FROM_SOURCE_REPOSITORY,
            $handlerException->source->getRepositoryPath()
        );
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
