<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

interface ExceptionCollectionHandlerInterface
{
    /**
     * @param \Throwable[] $exceptions
     */
    public function handle(array $exceptions): void;
}
