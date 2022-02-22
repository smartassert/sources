<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\Yaml\Exception\ParseException;

class UnparseableSourceFileException extends \Exception
{
    public function __construct(
        private string $path,
        private ParseException $parseException,
    ) {
        parent::__construct('', 0, $parseException);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getParseException(): ParseException
    {
        return $this->parseException;
    }
}
