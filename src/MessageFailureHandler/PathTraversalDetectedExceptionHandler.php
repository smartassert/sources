<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\GitSource;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Repository\SerializedSuiteRepository;
use League\Flysystem\PathTraversalDetected;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;
use SmartAssert\YamlFile\Exception\ProvisionException;

class PathTraversalDetectedExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    public function handle(SerializeSuiteException $exception): void
    {
        $serializedSuite = $exception->serializedSuite;
        $exception = $exception->handlerException;

        if ($exception instanceof SerializeException) {
            $exception = $exception->getPreviousException();
        }

        if (!$exception instanceof ProvisionException) {
            return;
        }

        $previousException = $exception->getPreviousException();
        if (!$previousException instanceof PathTraversalDetected) {
            return;
        }

        $suite = $serializedSuite->suite;
        $source = $suite->getSource();

        if (!$source instanceof GitSource) {
            return;
        }

        $serializedSuite->setPreparationFailed(FailureReason::GIT_REPOSITORY_OUT_OF_SCOPE, $source->getPath());
        $this->serializedSuiteRepository->save($serializedSuite);
    }
}
