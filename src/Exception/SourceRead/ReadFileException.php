<?php

declare(strict_types=1);

namespace App\Exception\SourceRead;

use Symfony\Component\Finder\SplFileInfo;

class ReadFileException extends AbstractSourceReadException
{
    public function __construct(SplFileInfo $sourceFile)
    {
        parent::__construct($sourceFile, 'Unable to read file: %s');
    }
}
