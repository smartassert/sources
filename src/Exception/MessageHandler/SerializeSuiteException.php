<?php

declare(strict_types=1);

namespace App\Exception\MessageHandler;

use App\Entity\SerializedSuite;
use App\Enum\SerializedSuite\FailureReason;

class SerializeSuiteException extends \Exception
{
    public function __construct(
        public readonly SerializedSuite $serializedSuite,
        public readonly \Throwable $handlerException,
        public readonly ?FailureReason $failureReason = null,
        public readonly ?string $failureMessage = null,
    ) {
        parent::__construct($handlerException->getMessage(), $handlerException->getCode(), $handlerException);
    }
}
