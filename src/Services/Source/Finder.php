<?php

declare(strict_types=1);

namespace App\Services\Source;

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
        $type = $source->getType();

        if ($this->fileSourceFinder->supports($type)) {
            return $this->fileSourceFinder->find($source);
        }

        if ($this->gitSourceFinder->supports($type)) {
            return $this->gitSourceFinder->find($source);
        }

        return null;
    }
}
