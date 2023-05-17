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
