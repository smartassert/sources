<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Symfony\Component\Filesystem\Filesystem;

class FileStoreManager
{
    public function __construct(
        private Filesystem $filesystem,
        private string $sourceFixturesPath,
        private string $fileStoreBasePath
    ) {
    }

    public function copyFixturesTo(string $relativePath): void
    {
        $this->filesystem->mirror(
            $this->sourceFixturesPath,
            sprintf('%s/%s', $this->fileStoreBasePath, $relativePath)
        );
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
