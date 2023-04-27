<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;
use SmartAssert\YamlFile\Exception\ProvisionException;

class ProvisionExceptionHandler implements SuiteSerializationExceptionHandlerInterface
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
