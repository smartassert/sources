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

    public function copySetTo(string $origin, string $target): void
    {
        $this->filesystem->mirror($this->getFixturePath($origin), sprintf('%s/%s', $this->fileStoreBasePath, $target));
    }

    public function copyTo(string $origin, string $target): void
    {
        $this->filesystem->copy($this->getFixturePath($origin), sprintf('%s/%s', $this->fileStoreBasePath, $target));
    }

    public function getFixturePath(string $relativePath): string
    {
        return $this->fixturesBasePath . $relativePath;
    }
}
