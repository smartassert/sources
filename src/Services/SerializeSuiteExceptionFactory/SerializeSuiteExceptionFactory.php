<?php

declare(strict_types=1);

namespace App\Services\SerializeSuiteExceptionFactory;

use App\Entity\SerializedSuiteInterface;
use App\Enum\SerializedSuite\FailureReason;
use App\Exception\MessageHandler\SerializeSuiteException;

class SerializeSuiteExceptionFactory
{
    /**
     * @param iterable<ExceptionHandlerInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {}

    public function create(SerializedSuiteInterface $serializedSuite, \Throwable $throwable): SerializeSuiteException
    {
        $exception = null;
        foreach ($this->handlers as $handler) {
            if (null === $exception) {
                $exception = $handler->handle($serializedSuite, $throwable);
            }
        }

        if (null === $exception) {
            $failureReason = FailureReason::UNKNOWN;
            $failureMessage = $throwable->getMessage();
            $exception = new SerializeSuiteException($serializedSuite, $throwable, $failureReason, $failureMessage);
        }

        return $exception;
    }
}
