<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;

class Finder
{
    public function __construct(
        private FileSourceFinder $fileSourceFinder,
        private GitSourceFinder $gitSourceFinder,
    ) {
    }

    public function find(SourceInterface $source): ?SourceInterface
    {
        if ($source instanceof FileSource) {
            return $this->fileSourceFinder->find($source);
        }

        if ($source instanceof GitSource) {
            return $this->gitSourceFinder->find($source);
        }

        return null;
    }
}
