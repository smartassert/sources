<?php

declare(strict_types=1);

namespace App\ErrorResponse;

use App\RequestField\FieldInterface;

interface BadRequestErrorInterface extends ErrorInterface
{
    public function getField(): FieldInterface;
}
