<?php

declare(strict_types=1);

namespace App\Exception\MessageHandler;

class PrepareException extends \Exception
{
    public function __construct(
        private \Throwable $exception,
    ) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }

    public function getHandlerException(): \Throwable
    {
        return $this->exception;
    }
}
