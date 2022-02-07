<?php

declare(strict_types=1);

namespace App\Exception\SourceRead;

class ReadFileException extends AbstractSourceReadException
{
    public function __construct(string $sourceFile)
    {
        parent::__construct($sourceFile, 'Unable to read file: %s');
    }
}
