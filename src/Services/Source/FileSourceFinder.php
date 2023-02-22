<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Repository\FileSourceRepository;

class FileSourceFinder
{
    public function __construct(
        private readonly FileSourceRepository $repository,
        private readonly OriginSourceFinder $originSourceFinder,
    ) {
    }

    public function find(string $userId, string $label): ?FileSource
    {
        $source = $this->originSourceFinder->find($this->repository, $userId, $label);

        return $source instanceof FileSource ? $source : null;
    }

    public function has(string $userId, string $label): bool
    {
        return $this->originSourceFinder->has($this->repository, $userId, $label);
    }
}
