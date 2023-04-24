<?php

declare(strict_types=1);

namespace App\Exception\MessageHandler;

use App\Entity\SerializedSuite;

class SuiteSerializationException extends \Exception
{
    public function __construct(
        public readonly SerializedSuite $serializedSuite,
        private \Throwable $exception,
    ) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }

    public function getHandlerException(): \Throwable
    {
        return $this->exception;
    }
}
