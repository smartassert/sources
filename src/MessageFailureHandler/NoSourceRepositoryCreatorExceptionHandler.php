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

        $handlerException = $throwable->handlerException;
        $serializedSuite = $throwable->serializedSuite;

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
