<?php

declare(strict_types=1);

namespace App\Exception;

use SmartAssert\ServiceRequest\Error\ErrorInterface;

class ErrorResponseException extends \Exception
{
    /**
     * @param int<400, 599> $code
     */
    public function __construct(
        public readonly ErrorInterface $error,
        int $code,
        ?\Throwable $previous = null
    ) {
        parent::__construct('', $code, $previous);
    }
}
