<?php

declare(strict_types=1);

namespace App\Exception\SourceRead;

use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractSourceReadException extends \Exception implements SourceReadExceptionInterface
{
    public function __construct(
        private readonly SplFileInfo $sourceFile,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(sprintf($message, $sourceFile->getRelativePathname()), $code, $previous);
    }

    public function getSourceFile(): SplFileInfo
    {
        return $this->sourceFile;
    }
}
