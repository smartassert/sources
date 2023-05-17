<?php

declare(strict_types=1);

namespace App\Exception\MessageHandler;

use App\Entity\SerializedSuite;

class SerializeSuiteException extends \Exception
{
    public function __construct(
        public readonly SerializedSuite $serializedSuite,
        public readonly \Throwable $handlerException,
    ) {
        parent::__construct($handlerException->getMessage(), $handlerException->getCode(), $handlerException);
    }
}
