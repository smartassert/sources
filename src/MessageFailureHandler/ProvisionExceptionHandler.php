<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use SmartAssert\YamlFile\Exception\ProvisionException;

class ProvisionExceptionHandler implements SuiteSerializationExceptionHandlerInterface
{
    /**
     * @param iterable<SuiteSerializationExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): void
    {
        if (!$exception instanceof ProvisionException) {
            return;
        }

        foreach ($this->handlers as $handler) {
            $handler->handle($serializedSuite, $exception->getPreviousException());
        }
    }
}
