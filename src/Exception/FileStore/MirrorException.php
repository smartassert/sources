<?php

declare(strict_types=1);

namespace App\Exception\FileStore;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class MirrorException extends \Exception implements FileStoreExceptionInterface
{
    public function __construct(
        private string $source,
        private string $target,
        private IOExceptionInterface $IOException
    ) {
        parent::__construct(
            sprintf('Failed to copy all files from "%s" to "%s"', $source, $target),
            0,
            $this->IOException
        );
    }

    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
