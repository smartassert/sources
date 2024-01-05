<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\DuplicateObjectErrorInterface;

class DuplicateObjectException extends FooException
{
    public function __construct(DuplicateObjectErrorInterface $error)
    {
        parent::__construct($error, 400);
    }
}
