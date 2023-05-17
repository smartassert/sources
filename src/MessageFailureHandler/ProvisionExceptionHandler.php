<?php

declare(strict_types=1);

namespace App\MessageFailureHandler;

use SmartAssert\YamlFile\Exception\ProvisionException;

class ProvisionExceptionHandler extends AbstractSpecificExceptionDelegatingHandler
{
    protected function handles(\Throwable $throwable): bool
    {
        return $throwable instanceof ProvisionException;
    }

    protected function getExceptionToHandle(\Throwable $throwable): \Throwable
    {
        return $throwable instanceof ProvisionException ? $throwable->getPreviousException() : $throwable;
    }
}
