<?php

declare(strict_types=1);

namespace App\Exception;

class SourceRepositoryCreationException extends \Exception
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }
}
