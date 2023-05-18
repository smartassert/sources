<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;
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

    public function handle(\Throwable $exception): void
    {
        if (!$exception instanceof SerializeSuiteException) {
            return;
        }

        $handlerException = $exception->handlerException;
        if (!$handlerException instanceof SerializeException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($exception);
        }
    }
}
