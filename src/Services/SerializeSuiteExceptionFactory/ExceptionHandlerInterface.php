<?php

declare(strict_types=1);

namespace App\Services\SerializeSuiteExceptionFactory;

use App\Entity\SerializedSuiteInterface;
use App\Exception\MessageHandler\SerializeSuiteException;

interface ExceptionHandlerInterface
{
    public function handle(SerializedSuiteInterface $serializedSuite, \Throwable $throwable): ?SerializeSuiteException;
}
