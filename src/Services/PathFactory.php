<?php

declare(strict_types=1);

namespace App\Services;

use League\Flysystem\PathNormalizer;

class PathFactory
{
    public function __construct(
        private string $basePath,
        private PathNormalizer $pathNormalizer,
    ) {
    }

    public function createAbsolutePath(string $relativePath): string
    {
        return '/' . $this->pathNormalizer->normalizePath($this->basePath . '/' . $relativePath);
    }
}
