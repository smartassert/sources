<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\StorageErrorInterface;

class StorageException extends ErrorResponseException
{
    public function __construct(StorageErrorInterface $error, \Throwable $previous)
    {
        parent::__construct($error, 500, $previous);
    }
}
