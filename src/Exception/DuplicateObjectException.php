<?php

declare(strict_types=1);

namespace App\Exception;

use SmartAssert\ServiceRequest\Error\DuplicateObjectErrorInterface;

class DuplicateObjectException extends ErrorResponseException
{
    public function __construct(DuplicateObjectErrorInterface $error)
    {
        parent::__construct($error, 400);
    }
}
