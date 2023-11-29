<?php

declare(strict_types=1);

namespace App\Exception;

use App\FooRequest\Field\Field;
use App\FooRequest\FieldInterface;
use App\FooResponse\ErrorInterface;

class ModifyReadOnlyEntityException extends \Exception implements HasHttpErrorCodeInterface, ErrorInterface
{
    /**
     * @param non-empty-string $type
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
    ) {
        parent::__construct(sprintf(
            'Cannot modify %s %s, entity is read-only',
            $this->type,
            $this->id
        ));
    }

    public function getErrorCode(): int
    {
        return 405;
    }

    public function getClass(): string
    {
        return 'modify_read_only';
    }

    public function getField(): FieldInterface
    {
        return new Field('id', $this->id);
    }

    public function getType(): string
    {
        return $this->type;
    }
}
