<?php

declare(strict_types=1);

namespace App\Request;

interface LabelledObjectRequestInterface
{
    /**
     * @return non-empty-string
     */
    public function getLabel(): string;
}
