<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\DuplicateObjectErrorInterface;
use App\RequestField\FieldInterface;

class DuplicateObjectException extends AbstractErrorException implements DuplicateObjectErrorInterface
{
    public function __construct(
        private readonly FieldInterface $field,
    ) {
        parent::__construct(DuplicateObjectErrorInterface::ERROR_CLASS, '', 400);
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    public function getType(): null
    {
        return null;
    }

    public function serialize(): array
    {
        return [
            'class' => DuplicateObjectErrorInterface::ERROR_CLASS,
            'field' => $this->field->jsonSerialize(),
        ];
    }
}
