<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\ErrorInterface;

class ErrorException extends \Exception implements ErrorInterface
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

    public function serialize(): array
    {
        $data = ['class' => $this->class];

        if (is_string($this->type)) {
            $data['type'] = $this->type;
        }

        return $data;
    }
}
