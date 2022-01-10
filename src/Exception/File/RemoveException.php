<?php

declare(strict_types=1);

namespace App\Exception\File;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class RemoveException extends \Exception implements MutationExceptionInterface, PathExceptionInterface
{
    public function __construct(
        private string $path,
        private IOExceptionInterface $IOException
    ) {
        parent::__construct(sprintf('Unable to remove "%s"', $path), 0, $IOException);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }
}
