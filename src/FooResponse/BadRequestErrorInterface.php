<?php

declare(strict_types=1);

namespace App\FooResponse;

use App\FooRequest\FieldInterface;

interface BadRequestErrorInterface extends ErrorInterface
{
    public function getField(): FieldInterface;
}
