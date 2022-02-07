<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Symfony\Component\Filesystem\Filesystem;

class FileStoreFixtureCreator
{
    public function __construct(
        private Filesystem $filesystem,
        private string $fileStoreBasePath,
        private string $fixturesBasePath,
    ) {
    }

    public function copyFixtureSetTo(string $origin, string $target): void
    {
        $this->filesystem->mirror(
            $this->getFixtureSetPath($origin),
            sprintf('%s/%s', $this->fileStoreBasePath, $target)
        );
    }

    public function getFixtureSetPath(string $relativePath): string
    {
        return $this->fixturesBasePath . $relativePath;
    }
}
