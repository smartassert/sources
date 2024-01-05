<?php

declare(strict_types=1);

namespace App\Exception;

abstract class AbstractErrorException extends \Exception
{
    /**
     * @param non-empty-string  $class
     * @param ?non-empty-string $type
     */
    public function __construct(
        private readonly string $class,
        private readonly ?string $type,
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

    /**
     * @return ?non-empty-string
     */
    public function getType(): ?string
    {
        return $this->type;
    }
}
