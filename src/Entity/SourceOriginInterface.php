<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceOriginInterface extends SourceInterface
{
    /**
     * @return string[]
     */
    public function getRunParameterNames(): array;
}
