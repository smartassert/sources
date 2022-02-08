<?php

declare(strict_types=1);

namespace App\Entity;

interface OriginSourceInterface extends SourceInterface
{
    /**
     * @return string[]
     */
    public function getRunParameterNames(): array;
}