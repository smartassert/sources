<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use SmartAssert\WorkerMessageFailedEventBundle\ExceptionCollectionHandlerInterface;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;

class SerializeSuiteExceptionHandler implements ExceptionCollectionHandlerInterface
{
    /**
     * @param iterable<ExceptionHandlerInterface> $handlers
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
