<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

use App\Exception\File\MutationExceptionInterface;
use App\Exception\File\PathExceptionInterface;
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
