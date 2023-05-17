<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use SmartAssert\YamlFile\Exception\Collection\SerializeException;

class SerializeExceptionHandler extends AbstractSpecificExceptionDelegatingHandler
{
    use HighPriorityTrait;

    protected function handles(\Throwable $throwable): bool
    {
        return $throwable instanceof SerializeException;
    }

    protected function getExceptionToHandle(\Throwable $throwable): \Throwable
    {
        return $throwable instanceof SerializeException ? $throwable->getPreviousException() : $throwable;
    }
}
