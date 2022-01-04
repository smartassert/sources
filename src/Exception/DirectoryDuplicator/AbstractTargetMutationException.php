<?php

declare(strict_types=1);

namespace App\Exception\DirectoryDuplicator;

use App\Model\FileLocatorInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class AbstractTargetMutationException extends AbstractFileLocatorException
{
    public function __construct(
        FileLocatorInterface $fileLocator,
        private IOExceptionInterface $IOException,
        string $message
    ) {
        parent::__construct($fileLocator, $message, 0, $IOException);
    }

    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }
}
