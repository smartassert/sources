<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class RemoveException extends AbstractFilePathException
{
    public function __construct(
        string $path,
        private IOExceptionInterface $IOException
    ) {
        parent::__construct($path, sprintf('Unable to remove "%s"', $path), $IOException);
    }

    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }
}
