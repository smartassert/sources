<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\GitSource;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;
use App\Repository\SerializedSuiteRepository;
use League\Flysystem\PathTraversalDetected;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;
use SmartAssert\YamlFile\Exception\ProvisionException;

class PathTraversalDetectedExceptionHandler implements ExceptionHandlerInterface
{
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

        if ($throwable instanceof SerializeException) {
            $throwable = $throwable->getPrevious();
        }

        if (!$throwable instanceof ProvisionException) {
            return;
        }

        $throwable = $throwable->getPreviousException();
        if (!$throwable instanceof PathTraversalDetected) {
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
