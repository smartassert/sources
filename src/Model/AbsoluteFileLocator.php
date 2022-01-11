<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\File\NonAbsolutePathException;
use App\Exception\File\OutOfScopeException;
use Symfony\Component\Filesystem\Path;

class AbsoluteFileLocator implements AppendableFileLocatorInterface
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

    /**
     * @throws OutOfScopeException
     */
    public function append(string $path): AbsoluteFileLocator
    {
        $newPath = Path::canonicalize($this->path . '/' . $path);
        if (false === Path::isBasePath($this->path, $newPath)) {
            throw new OutOfScopeException($newPath, $this->path);
        }

        $new = clone $this;
        $new->path = Path::canonicalize($this->path . '/' . $path);

        return $new;
    }
}
