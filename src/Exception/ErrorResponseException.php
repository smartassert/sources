<?php

declare(strict_types=1);

namespace App\Exception;

use SmartAssert\ServiceRequest\Error\ErrorInterface;

class ErrorResponseException extends \Exception
{
    public function __construct(
        public readonly ErrorInterface $error,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct('', $code, $previous);
    }
}
