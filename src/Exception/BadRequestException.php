<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\FooErrorInterface;

class BadRequestException extends FooException
{
    public function __construct(FooErrorInterface $error)
    {
        parent::__construct($error, 400);
    }
}
