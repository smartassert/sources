<?php

declare(strict_types=1);

namespace App\Exception\DirectoryDuplicator;

use App\Model\FileLocatorInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class DuplicationException extends \Exception implements DirectoryDuplicatorExceptionInterface
{
    public function __construct(
        private FileLocatorInterface $source,
        private FileLocatorInterface $target,
        private IOExceptionInterface $IOException
    ) {
        parent::__construct(
            sprintf('Failed to copy all files from "%s" to "%s"', $source, $target),
            0,
            $this->IOException
        );
    }

    public function getSource(): FileLocatorInterface
    {
        return $this->source;
    }

    public function getTarget(): FileLocatorInterface
    {
        return $this->target;
    }

    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }
}
