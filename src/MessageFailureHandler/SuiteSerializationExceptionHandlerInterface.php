<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Entity\SerializedSuite;

interface SuiteSerializationExceptionHandlerInterface
{
    public function handle(SerializedSuite $serializedSuite, \Throwable $exception): bool;
}
