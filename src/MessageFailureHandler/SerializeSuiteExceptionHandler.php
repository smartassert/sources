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
        private readonly UnknownExceptionHandler $unknownExceptionHandler,
    ) {
    }

    public function handle(array $exceptions): void
    {
        $exception = $exceptions[0] ?? null;
        if (!$exception instanceof SerializeSuiteException) {
            return;
        }

        $result = false;
        foreach ($this->handlers as $handler) {
            $handlerResult = $handler->handle($exception->serializedSuite, $exception->handlerException);

            if ($handlerResult) {
                $result = true;
            }
        }

        if (false === $result) {
            $this->unknownExceptionHandler->handle($exception->serializedSuite, $exception->handlerException);
        }
    }
}
