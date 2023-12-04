<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\BadRequestErrorInterface;
use App\ErrorResponse\DuplicateItemInterface;
use App\RequestField\Field\Field;
use App\RequestField\FieldInterface;

class DuplicateFilePathException extends \Exception implements BadRequestErrorInterface, DuplicateItemInterface
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

    public function getDuplicationOf(): string
    {
        return 'file path';
    }
}
