<?php

declare(strict_types=1);

namespace App\Model;

class AbsoluteFileLocator implements FileLocatorInterface
{
    public function __construct(
        private string $path,
    ) {

    }

    public function getPath(): string
    {
        // TODO: Implement getPath() method.
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
    }
}
