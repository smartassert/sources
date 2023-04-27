<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use App\Repository\SerializedSuiteRepository;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;

class SerializeExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    //    public function __construct(
    //        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    //    ) {
    //    }

    /**
     * @var SuiteSerializationExceptionHandlerInterface[]
     */
    private readonly array $handlers;

    /**
     * @param array<mixed> $exceptionHandlers
     */
    public function __construct(
        array $exceptionHandlers,
    ) {
        $handlers = [];

        foreach ($exceptionHandlers as $exceptionCollectionHandler) {
            if ($exceptionCollectionHandler instanceof SuiteSerializationExceptionHandlerInterface) {
                $handlers[] = $exceptionCollectionHandler;
            }
        }

        $this->handlers = $handlers;
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        if (!$exception instanceof SerializeException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($serializedSuite, $exception->getPreviousException());
        }
    }
}