<?php

declare(strict_types=1);

namespace App\Exception;

use App\FooResponse\BadRequestErrorInterface;
use App\FooResponse\HasHttpStatusCodeInterface;
use App\RequestField\FieldInterface;

class InvalidRequestException extends \Exception implements HasHttpStatusCodeInterface, BadRequestErrorInterface
{
    /**
     * @param non-empty-string  $class
     * @param ?non-empty-string $type
     */
    public function __construct(
        private readonly string $class,
        private readonly FieldInterface $field,
        private readonly ?string $type,
    ) {
        $message = $class . ': ' . $class;
        if (is_string($type)) {
            $message .= ' ' . $type;
        }

        parent::__construct($message, 400);
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
