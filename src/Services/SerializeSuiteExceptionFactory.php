<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\GitRepositoryException;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Exception\SourceRepositoryCreationException;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnableToWriteSerializedSuiteException;
use League\Flysystem\PathTraversalDetected;

class SerializeSuiteExceptionFactory
{
    public function create(SerializedSuite $serializedSuite, \Throwable $throwable): SerializeSuiteException
    {
        $failureReason = FailureReason::UNKNOWN;
        $failureMessage = $throwable->getMessage();

        if ($throwable instanceof NoSourceRepositoryCreatorException) {
            $failureReason = FailureReason::UNSERIALIZABLE_SOURCE_TYPE;
            $failureMessage = $throwable->source->getType()->value;
        }

        if ($throwable instanceof PathTraversalDetected) {
            $suite = $serializedSuite->suite;
            $source = $suite->getSource();

            if ($source instanceof GitSource) {
                $failureReason = FailureReason::GIT_REPOSITORY_OUT_OF_SCOPE;
                $failureMessage = $source->getPath();
            }
        }

        if ($throwable instanceof UnableToWriteSerializedSuiteException) {
            $failureReason = FailureReason::UNABLE_TO_WRITE_TO_TARGET;
            $failureMessage = $throwable->path;
        }

        if ($throwable instanceof SourceRepositoryReaderNotFoundException) {
            $failureReason = FailureReason::UNABLE_TO_READ_FROM_SOURCE_REPOSITORY;
            $failureMessage = $throwable->source->getRepositoryPath();
        }

        if ($throwable instanceof SourceRepositoryCreationException) {
            $gitRepositoryException = $throwable->getPrevious();
            if ($gitRepositoryException instanceof GitRepositoryException) {
                $failureMessage = $gitRepositoryException->getMessage();

                if (GitRepositoryException::CODE_GIT_CLONE_FAILED === $gitRepositoryException->getCode()) {
                    $failureReason = FailureReason::GIT_CLONE;
                }

                if (GitRepositoryException::CODE_GIT_CHECKOUT_FAILED === $gitRepositoryException->getCode()) {
                    $failureReason = FailureReason::GIT_CHECKOUT;
                }
            }
        }

        return new SerializeSuiteException($serializedSuite, $throwable, $failureReason, $failureMessage);
    }
}
