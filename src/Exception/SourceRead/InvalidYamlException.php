<?php

declare(strict_types=1);

namespace App\Exception\SourceRead;

use Symfony\Component\Yaml\Exception\ParseException;

class InvalidYamlException extends AbstractSourceReadException
{
    public function __construct(
        private readonly string $sourceFile,
        private readonly ParseException $parseException
    ) {
        parent::__construct($sourceFile, 'Invalid yaml in file: %s', 0, $this->parseException);
    }

    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    public function getParseException(): ParseException
    {
        return $this->parseException;
    }
}
