<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

interface ExceptionHandlerInterface
{
    /**
     * @param \Throwable[] $exceptions
     */
    public function handle(array $exceptions): void;
}
