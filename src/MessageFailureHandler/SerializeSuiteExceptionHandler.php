<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use SmartAssert\WorkerMessageFailedEventBundle\ExceptionCollectionHandlerInterface;

class SerializeSuiteExceptionHandler implements ExceptionCollectionHandlerInterface
{
    /**
     * @param iterable<SuiteSerializationExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function handle(array $exceptions): void
    {
        foreach ($exceptions as $exception) {
            foreach ($this->handlers as $handler) {
                $handler->handle($exception);
            }
        }
    }
}
