<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\ErrorResponse\SizeInterface;

readonly class StringRequirements extends ScalarRequirements
{
    public function __construct(SizeInterface $size)
    {
        parent::__construct('string', $size);
    }
}