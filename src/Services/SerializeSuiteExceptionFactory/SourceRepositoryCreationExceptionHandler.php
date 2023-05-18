<?php

declare(strict_types=1);

namespace App\Services\SerializeSuiteExceptionFactory;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\GitRepositoryException;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\SourceRepositoryCreationException;

class SourceRepositoryCreationExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(SerializedSuite $serializedSuite, \Throwable $throwable): ?SerializeSuiteException
    {
        if (!$throwable instanceof SourceRepositoryCreationException) {
            return null;
        }

        $gitRepositoryException = $throwable->getPrevious();
        if (!$gitRepositoryException instanceof GitRepositoryException) {
            return null;
        }

        $failureReason = FailureReason::GIT_UNKNOWN;
        $failureMessage = $gitRepositoryException->getMessage();

        if (GitRepositoryException::CODE_GIT_CLONE_FAILED === $gitRepositoryException->getCode()) {
            $failureReason = FailureReason::GIT_CLONE;
        }

        if (GitRepositoryException::CODE_GIT_CHECKOUT_FAILED === $gitRepositoryException->getCode()) {
            $failureReason = FailureReason::GIT_CHECKOUT;
        }

        return new SerializeSuiteException($serializedSuite, $throwable, $failureReason, $failureMessage);
    }
}
