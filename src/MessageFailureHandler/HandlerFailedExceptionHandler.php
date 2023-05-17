<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use Symfony\Component\Messenger\Exception\HandlerFailedException;

class HandlerFailedExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @param iterable<ExceptionCollectionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function handle(\Throwable $throwable): void
    {
        if (!$throwable instanceof HandlerFailedException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($throwable->getNestedExceptions());
        }
    }
}
