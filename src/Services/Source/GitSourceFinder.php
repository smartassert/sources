<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Repository\GitSourceRepository;

class GitSourceFinder
{
    public function __construct(
        private readonly GitSourceRepository $repository,
        private readonly OriginSourceFinder $originSourceFinder,
    ) {
    }

    public function find(string $userId, string $label): ?GitSource
    {
        $source = $this->originSourceFinder->find($this->repository, $userId, $label);

        return $source instanceof GitSource ? $source : null;
    }
}
