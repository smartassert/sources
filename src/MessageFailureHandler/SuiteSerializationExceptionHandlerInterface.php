<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

interface SuiteSerializationExceptionHandlerInterface
{
    public function handle(\Throwable $exception): void;
}
