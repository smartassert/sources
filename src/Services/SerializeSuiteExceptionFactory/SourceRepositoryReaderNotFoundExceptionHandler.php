<?php

declare(strict_types=1);

namespace App\Services\SerializeSuiteExceptionFactory;

use App\Entity\SerializedSuiteInterface;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\SourceRepositoryReaderNotFoundException;

class SourceRepositoryReaderNotFoundExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(SerializedSuiteInterface $serializedSuite, \Throwable $throwable): ?SerializeSuiteException
    {
        if (!$throwable instanceof SourceRepositoryReaderNotFoundException) {
            return null;
        }

        return new SerializeSuiteException(
            $serializedSuite,
            $throwable,
            FailureReason::UNABLE_TO_READ_FROM_SOURCE_REPOSITORY,
            $throwable->source->getRepositoryPath(),
        );
    }
}
