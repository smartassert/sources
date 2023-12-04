<?php

declare(strict_types=1);

namespace App\FooRequest;

interface RequirementsInterface
{
    /**
     * @return non-empty-string
     */
    public function getDataType(): string;
}
