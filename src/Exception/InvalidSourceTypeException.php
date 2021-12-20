<?php

declare(strict_types=1);

namespace App\Exception;

class InvalidSourceTypeException extends \Exception
{
    public function __construct(private string $type)
    {
        parent::__construct(
            sprintf('Invalid SourceType "%s"', $this->type)
        );
    }
}
