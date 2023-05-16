<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;

class SerializeSuiteExceptionHandler implements ExceptionCollectionHandlerInterface
{
    /**
     * @var SuiteSerializationExceptionHandlerInterface[]
     */
    private readonly array $handlers;

    /**
     * @param array<mixed> $exceptionHandlers
     */
    public function __construct(
        array $exceptionHandlers,
        private readonly UnknownExceptionHandler $unknownExceptionHandler,
    ) {
        $handlers = [];

        foreach ($exceptionHandlers as $exceptionCollectionHandler) {
            if ($exceptionCollectionHandler instanceof SuiteSerializationExceptionHandlerInterface) {
                $handlers[] = $exceptionCollectionHandler;
            }
        }

        $this->handlers = $handlers;
    }

    public function handle(array $exceptions): bool
    {
        $exception = $exceptions[0] ?? null;
        if (!$exception instanceof SerializeSuiteException) {
            return false;
        }

        $result = false;
        foreach ($this->handlers as $handler) {
            $handlerResult = $handler->handle($exception->serializedSuite, $exception->handlerException);

            if ($handlerResult) {
                $result = true;
            }
        }

        if (false === $result) {
            $result = $this->unknownExceptionHandler->handle($exception->serializedSuite, $exception->handlerException);
        }

        return $result;
    }
}
