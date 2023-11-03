<?php

declare(strict_types=1);

namespace App\Request;

interface ObjectRequestInterface
{
    /**
     * @return non-empty-string
     */
    public function getObjectType(): string;
}
