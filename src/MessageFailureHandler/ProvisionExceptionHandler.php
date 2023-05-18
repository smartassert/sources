<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;

class ProvisionExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @param iterable<ExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function handle(\Throwable $throwable): void
    {
        if (!$throwable instanceof SerializeSuiteException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($throwable);
        }
    }
}
