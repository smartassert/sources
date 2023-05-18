<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;

class ProvisionExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    /**
     * @param iterable<SuiteSerializationExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function handle(\Throwable $exception): void
    {
        if (!$exception instanceof SerializeSuiteException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($exception);
        }
    }
}
