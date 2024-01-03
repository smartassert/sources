<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\BadRequestErrorInterface;
use App\RequestField\FieldInterface;

class BadRequestException extends \Exception implements BadRequestErrorInterface
{
    /**
     * @param non-empty-string $type
     */
    public function __construct(
        private readonly FieldInterface $field,
        private readonly string $type,
    ) {
        $message = 'bad_request: ' . $field->getName() . ' ' . $this->type;

        parent::__construct($message, 400);
    }

    public function getClass(): string
    {
        return 'bad_request';
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    /**
     * @return non-empty-string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    public function serialize(): array
    {
        return [
            'class' => $this->getClass(),
            'type' => $this->getType(),
            'field' => $this->field->jsonSerialize(),
        ];
    }
}
