<?php

declare(strict_types=1);

namespace App\Exception;

interface HasHttpErrorCodeInterface extends \Throwable
{
    public function getErrorCode(): int;
}
