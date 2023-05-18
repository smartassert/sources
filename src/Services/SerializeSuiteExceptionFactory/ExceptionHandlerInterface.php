<?php

declare(strict_types=1);

namespace App\Services\SerializeSuiteExceptionFactory;

use App\Entity\SerializedSuite;
use App\Exception\MessageHandler\SerializeSuiteException;

interface ExceptionHandlerInterface
{
    public function handle(SerializedSuite $serializedSuite, \Throwable $throwable): ?SerializeSuiteException;
}
