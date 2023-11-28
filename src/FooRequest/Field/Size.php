<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooResponse\SizeInterface;

readonly class Size implements SizeInterface
{
    public function __construct(
        private int $minimum,
        private int $maximum,
    ) {
    }

    public function getMinimum(): int
    {
        return $this->minimum;
    }

    public function getMaximum(): int
    {
        return $this->maximum;
    }
}
