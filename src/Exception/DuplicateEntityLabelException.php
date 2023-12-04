<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\BadRequestErrorInterface;
use App\Request\LabelledObjectRequestInterface;
use App\Request\ObjectRequestInterface;
use App\RequestField\Field\Field;
use App\RequestField\FieldInterface;

class DuplicateEntityLabelException extends \Exception implements BadRequestErrorInterface
{
    public function __construct(
        public readonly LabelledObjectRequestInterface&ObjectRequestInterface $request,
    ) {
        parent::__construct();
    }

    public function getClass(): string
    {
        return 'duplicate';
    }

    public function getField(): FieldInterface
    {
        return new Field('label', $this->request->getLabel());
    }

    public function getType(): ?string
    {
        return null;
    }
}
