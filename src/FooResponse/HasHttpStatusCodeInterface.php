<?php

declare(strict_types=1);

namespace App\FooResponse;

interface HasHttpStatusCodeInterface
{
    public function getStatusCode(): int;
}
