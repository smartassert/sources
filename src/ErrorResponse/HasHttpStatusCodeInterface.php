<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface HasHttpStatusCodeInterface
{
    public function getStatusCode(): int;
}
