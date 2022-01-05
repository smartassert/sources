<?php

declare(strict_types=1);

namespace App\Exception\DirectoryDuplicator;

use App\Model\FileLocatorInterface;

abstract class AbstractFileLocatorException extends \Exception implements DirectoryDuplicatorExceptionInterface
{
    public function __construct(
        private FileLocatorInterface $fileLocator,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getFileLocator(): FileLocatorInterface
    {
        return $this->fileLocator;
    }
}
