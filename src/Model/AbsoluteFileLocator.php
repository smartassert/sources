<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\File\NonAbsolutePathException;
use Symfony\Component\Filesystem\Path;

class AbsoluteFileLocator implements FileLocatorInterface
{
    /**
     * @throws NonAbsolutePathException
     */
    public function __construct(
        private string $path,
    ) {
        if (false === Path::isAbsolute($path)) {
            throw new NonAbsolutePathException($path);
        }
    }

    public function __toString()
    {
        return $this->getPath();
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
