<?php

declare(strict_types=1);

namespace App\Exception;

abstract class AbstractErrorException extends \Exception
{
    /**
     * @param non-empty-string $class
     */
    public function __construct(
        private readonly string $class,
        string $message,
        int $code,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
