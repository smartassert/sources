<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\BadRequestErrorInterface as BadRequestError;
use App\ErrorResponse\SerializableDuplicateObjectErrorInterface as SerializableDuplicateObjectError;
use App\RequestField\FieldInterface;

class DuplicateObjectException extends \Exception implements BadRequestError, SerializableDuplicateObjectError
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

    public function jsonSerialize(): array
    {
        return [
            'class' => $this->getClass(),
            'field' => $this->field->jsonSerialize(),
        ];
    }
}
