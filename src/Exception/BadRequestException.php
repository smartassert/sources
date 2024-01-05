<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\BadRequestErrorInterface;

class BadRequestException extends FooException
{
    public function __construct(BadRequestErrorInterface $error)
    {
        parent::__construct($error, 400);
    }
}
