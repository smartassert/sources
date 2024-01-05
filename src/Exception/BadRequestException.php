<?php

declare(strict_types=1);

namespace App\Exception;

use SmartAssert\ServiceRequest\Error\BadRequestErrorInterface;

class BadRequestException extends ErrorResponseException
{
    public function __construct(BadRequestErrorInterface $error)
    {
        parent::__construct($error, 400);
    }
}
