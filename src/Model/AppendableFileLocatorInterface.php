<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\File\OutOfScopeException;

interface AppendableFileLocatorInterface extends FileLocatorInterface
{
    /**
     * Append a path to the end of the existing path.
     *
     * The current path MUST be a base path of the new pat.
     * An OutOfScopeException MUST be thrown if this is not the case.
     *
     * @throws OutOfScopeException
     */
    public function append(string $path): self;
}
