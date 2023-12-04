<?php

declare(strict_types=1);

namespace App\Request;

class FileSourceRequest implements LabelledObjectRequestInterface
{
    public const PARAMETER_LABEL = 'label';

    /**
     * @param non-empty-string $label
     */
    public function __construct(
        public readonly string $label,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
