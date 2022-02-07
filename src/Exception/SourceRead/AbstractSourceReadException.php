<?php

declare(strict_types=1);

namespace App\Exception\SourceRead;

abstract class AbstractSourceReadException extends \Exception implements SourceReadExceptionInterface
{
    public function __construct(
        private readonly string $sourceFile,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(sprintf($message, $sourceFile), $code, $previous);
    }

    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }
}
