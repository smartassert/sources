<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;

class SerializeExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    use HighPriorityTrait;

    /**
     * @param iterable<SuiteSerializationExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

//    protected function handles(\Throwable $throwable): bool
//    {
//        return $throwable instanceof SerializeException;
//    }
//
//    protected function getExceptionToHandle(\Throwable $throwable): \Throwable
//    {
//        return $throwable instanceof SerializeException ? $throwable->getPreviousException() : $throwable;
//    }
    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        if (!$exception instanceof SerializeException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($serializedSuite, $exception);
        }
    }
}
