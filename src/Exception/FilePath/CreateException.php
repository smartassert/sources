<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class CreateException extends AbstractFilePathException
{
    public function __construct(
        string $path,
        private IOExceptionInterface $IOException
    ) {
        parent::__construct(sprintf('Unable to create "%s"', $path), $path);
    }

    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }
}
