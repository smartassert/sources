<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\BadRequestErrorInterface;
use App\RequestField\FieldInterface;

class DuplicateObjectException extends \Exception implements BadRequestErrorInterface
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
}
