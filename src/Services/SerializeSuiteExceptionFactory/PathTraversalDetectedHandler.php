<?php

declare(strict_types=1);

namespace App\Services\SerializeSuiteExceptionFactory;

use App\Entity\GitSource;
use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use League\Flysystem\PathTraversalDetected;

class PathTraversalDetectedHandler implements ExceptionHandlerInterface
{
    public function handle(SerializedSuite $serializedSuite, \Throwable $throwable): ?SerializeSuiteException
    {
        if (!$throwable instanceof PathTraversalDetected) {
            return null;
        }

        $suite = $serializedSuite->suite;
        $source = $suite->getSource();

        if (!$source instanceof GitSource) {
            return null;
        }

        return new SerializeSuiteException(
            $serializedSuite,
            $throwable,
            FailureReason::GIT_REPOSITORY_OUT_OF_SCOPE,
            $source->getPath(),
        );
    }
}
