<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

interface ExceptionHandlerInterface
{
    public function handle(\Throwable $throwable): void;
}
