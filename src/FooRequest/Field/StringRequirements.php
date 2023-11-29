<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooResponse\SizeInterface;

readonly class StringRequirements extends ScalarRequirements
{
    public function __construct(SizeInterface $size)
    {
        parent::__construct('string', $size, 0 === $size->getMinimum());
    }
}
