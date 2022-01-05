<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Symfony\Component\Filesystem\Filesystem;

class SourceFixtureRemover
{
    public function __construct(
        private Filesystem $filesystem,
        private string $fileStoreBasePath
    ) {
    }

    public function clear(): void
    {
        $directoryIterator = new \DirectoryIterator($this->fileStoreBasePath);

        /**
         * @var \DirectoryIterator $item
         */
        foreach ($directoryIterator as $item) {
            if (true === $item->isDir() && false === $item->isDot()) {
                $this->filesystem->remove($item->getPathname());
            }
        }
    }
}
