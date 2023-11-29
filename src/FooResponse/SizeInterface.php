<?php

declare(strict_types=1);

namespace App\FooResponse;

interface SizeInterface
{
    public function getMinimum(): int;

    public function getMaximum(): ?int;
}
