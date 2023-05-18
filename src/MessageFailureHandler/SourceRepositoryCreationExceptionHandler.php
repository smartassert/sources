<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Enum\SerializedSuite\FailureReason;
use App\Exception\GitRepositoryException;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\SourceRepositoryCreationException;
use App\Repository\SerializedSuiteRepository;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;

class SourceRepositoryCreationExceptionHandler implements ExceptionHandlerInterface
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

        if (!$handlerException instanceof SourceRepositoryCreationException) {
            return;
        }

        $sourceHandlerException = $handlerException->getPrevious();
        if (!$sourceHandlerException instanceof GitRepositoryException) {
            return;
        }

        if (GitRepositoryException::CODE_GIT_CLONE_FAILED === $sourceHandlerException->getCode()) {
            $serializedSuite->setPreparationFailed(
                FailureReason::GIT_CLONE,
                $sourceHandlerException->getMessage()
            );
            $this->serializedSuiteRepository->save($serializedSuite);
        }

        if (GitRepositoryException::CODE_GIT_CHECKOUT_FAILED === $sourceHandlerException->getCode()) {
            $serializedSuite->setPreparationFailed(
                FailureReason::GIT_CHECKOUT,
                $sourceHandlerException->getMessage()
            );
            $this->serializedSuiteRepository->save($serializedSuite);
        }
    }
}
