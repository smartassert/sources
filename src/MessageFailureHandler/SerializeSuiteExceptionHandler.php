<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;

class SerializeSuiteExceptionHandler implements ExceptionCollectionHandlerInterface
{
    /**
     * @param iterable<SerializeSuiteSubExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function handle(array $exceptions): void
    {
        $exception = $exceptions[0] ?? null;
        if (!$exception instanceof SerializeSuiteException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($exception->serializedSuite, $exception->handlerException);
        }
    }
}
