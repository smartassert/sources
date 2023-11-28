<?php

declare(strict_types=1);

namespace App\FooRequest\Field;

use App\FooResponse\SizeInterface;

readonly class StringRequirements extends Requirements
{
    public function __construct(SizeInterface $size, bool $canBeEmpty)
    {
        parent::__construct('string', $size, $canBeEmpty);
    }
}
