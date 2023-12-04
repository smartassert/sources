<?php

declare(strict_types=1);

namespace App\Exception;

interface HasHttpErrorCodeInterface
{
    public function getErrorCode(): int;
}
