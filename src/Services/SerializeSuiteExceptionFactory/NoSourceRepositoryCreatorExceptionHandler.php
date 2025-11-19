<?php

declare(strict_types=1);

namespace App\Services\SerializeSuiteExceptionFactory;

use App\Entity\SerializedSuiteInterface;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Exception\NoSourceRepositoryCreatorException;

class NoSourceRepositoryCreatorExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(SerializedSuiteInterface $serializedSuite, \Throwable $throwable): ?SerializeSuiteException
    {
        if (!$throwable instanceof NoSourceRepositoryCreatorException) {
            return null;
        }

        return new SerializeSuiteException(
            $serializedSuite,
            $throwable,
            FailureReason::UNSERIALIZABLE_SOURCE_TYPE,
            $throwable->source->getType()->value,
        );
    }
}
