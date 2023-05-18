<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use App\Exception\MessageHandler\SerializeSuiteException;

interface FooInterface
{
    public function handle(SerializeSuiteException $exception): void;
}
