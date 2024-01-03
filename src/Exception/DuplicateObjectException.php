<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\RequestFieldErrorInterface as RequestFieldError;
use App\RequestField\FieldInterface;

class DuplicateObjectException extends \Exception implements RequestFieldError
{
    public function __construct(
        private readonly FieldInterface $field,
    ) {
        parent::__construct();
    }

    public function getClass(): string
    {
        return 'duplicate';
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    public function getType(): ?string
    {
        return null;
    }

    public function serialize(): array
    {
        return [
            'class' => $this->getClass(),
            'field' => $this->field->jsonSerialize(),
        ];
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}
