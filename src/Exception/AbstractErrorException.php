<?php

declare(strict_types=1);

namespace App\Exception;

abstract class AbstractErrorException extends \Exception
{
    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
