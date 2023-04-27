<?php

declare(strict_types=1);

namespace App\Exception;

class UnableToWriteSerializedSuiteException extends \Exception
{
    public function __construct(
        public readonly string $path,
        public readonly string $content,
        public readonly \Throwable $previous
    ) {
        parent::__construct(sprintf('Unable to write serialized suite to "%s"', $path), 0, $previous);
    }
}
