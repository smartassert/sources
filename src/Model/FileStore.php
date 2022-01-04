<?php

declare(strict_types=1);

namespace App\Model;

class FileStore implements FileLocatorInterface
{
    use StringableFileLocatorTrait;

    public function __construct(
        private string $basePath,
        private FileLocatorInterface $fileLocator,
    ) {
    }

    public function getPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->basePath,
            $this->fileLocator->getPath()
        );
    }
}
