<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface DuplicateItemInterface
{
    /**
     * @return non-empty-string
     */
    public function getDuplicationOf(): string;
}
