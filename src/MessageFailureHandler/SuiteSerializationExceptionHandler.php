<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SuiteSerializationException;

class SuiteSerializationExceptionHandler implements ExceptionCollectionHandlerInterface
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
    ) {
        $handlers = [];

        foreach ($exceptionHandlers as $exceptionCollectionHandler) {
            if ($exceptionCollectionHandler instanceof SuiteSerializationExceptionHandlerInterface) {
                $handlers[] = $exceptionCollectionHandler;
            }
        }

        $this->handlers = $handlers;
    }

    public function handle(array $exceptions): void
    {
        $exception = $exceptions[0] ?? null;
        if (!$exception instanceof SuiteSerializationException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($exception->serializedSuite, $exception->handlerException);
        }
    }
}
