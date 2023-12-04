<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface SizeInterface
{
    public function getMinimum(): int;

    public function getMaximum(): ?int;
}
