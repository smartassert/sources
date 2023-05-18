<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;
use SmartAssert\WorkerMessageFailedEventBundle\ExceptionHandlerInterface;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;

class SerializeExceptionHandler implements ExceptionHandlerInterface
{
    use HighPriorityTrait;

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

        $handlerException = $throwable->handlerException;
        if (!$handlerException instanceof SerializeException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($throwable);
        }
    }
}
