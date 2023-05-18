<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Repository\SerializedSuiteRepository;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;

class NoSourceRepositoryCreatorExceptionHandler implements ExceptionHandlerInterface
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
        $throwable = $throwable->getPrevious();

        if (!$throwable instanceof NoSourceRepositoryCreatorException) {
            return;
        }

        $serializedSuite->setPreparationFailed(
            FailureReason::UNSERIALIZABLE_SOURCE_TYPE,
            $throwable->source->getType()->value
        );
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
