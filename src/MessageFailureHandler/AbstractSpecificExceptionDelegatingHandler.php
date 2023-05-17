<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;

abstract class AbstractSpecificExceptionDelegatingHandler implements SuiteSerializationExceptionHandlerInterface
{
    /**
     * @param iterable<SuiteSerializationExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        if (!$this->handles($exception)) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($serializedSuite, $this->getExceptionToHandle($exception));
        }
    }

    abstract protected function handles(\Throwable $throwable): bool;

    abstract protected function getExceptionToHandle(\Throwable $throwable): \Throwable;
}
