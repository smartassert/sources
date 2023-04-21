<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SuiteSerializationException;

class SuiteSerializationExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(array $exceptions): void
    {
        $exception = $exceptions[0] ?? null;
        if (!$exception instanceof SuiteSerializationException) {
            return;
        }
    }
}
