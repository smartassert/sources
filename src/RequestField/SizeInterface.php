<?php

declare(strict_types=1);

namespace App\RequestField;

interface SizeInterface
{
    public function getMinimum(): int;

    public function getMaximum(): ?int;
}
