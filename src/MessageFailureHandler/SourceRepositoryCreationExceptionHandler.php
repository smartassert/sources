<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\GitRepositoryException;
use App\Exception\SourceRepositoryCreationException;
use App\Repository\SerializedSuiteRepository;

class SourceRepositoryCreationExceptionHandler implements SerializeSuiteSubExceptionHandlerInterface
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): bool
    {
        if (!$exception instanceof SourceRepositoryCreationException) {
            return false;
        }

        $sourceHandlerException = $exception->getPrevious();
        if (!$sourceHandlerException instanceof GitRepositoryException) {
            return false;
        }

        if (GitRepositoryException::CODE_GIT_CLONE_FAILED === $sourceHandlerException->getCode()) {
            $serializedSuite->setPreparationFailed(
                FailureReason::GIT_CLONE,
                $sourceHandlerException->getMessage()
            );
            $this->serializedSuiteRepository->save($serializedSuite);

            return true;
        }

        if (GitRepositoryException::CODE_GIT_CHECKOUT_FAILED === $sourceHandlerException->getCode()) {
            $serializedSuite->setPreparationFailed(
                FailureReason::GIT_CHECKOUT,
                $sourceHandlerException->getMessage()
            );
            $this->serializedSuiteRepository->save($serializedSuite);

            return true;
        }

        return false;
    }
}
