<?php

declare(strict_types=1);

namespace App\Exception;

use App\FooRequest\Field\Field;
use App\FooRequest\FieldInterface;
use App\FooResponse\ErrorInterface;

class DuplicateFilePathException extends \Exception implements ErrorInterface
{
    public function __construct(
        public readonly string $path,
    ) {
        parent::__construct();
    }

    public function getClass(): string
    {
        return 'duplicate';
    }

    public function getField(): FieldInterface
    {
        return new Field('filename', $this->path);
    }

    public function getType(): ?string
    {
        return null;
    }
}
