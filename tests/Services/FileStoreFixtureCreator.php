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

    public function copyFixtureSetTo(string $setIdentifier, string $relativePath): void
    {
        $this->filesystem->mirror(
            $this->getFixtureSetPath($setIdentifier),
            sprintf('%s/%s', $this->fileStoreBasePath, $relativePath)
        );
    }

    public function getFixtureSetPath(string $setIdentifier): string
    {
        return $this->fixturesBasePath . $setIdentifier;
    }
}
