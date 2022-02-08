<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Repository\GitSourceRepository;

class GitSourceFinder implements TypeFinderInterface
{
    public function __construct(
        private GitSourceRepository $repository
    ) {
    }

    public function find(SourceInterface $source): ?GitSource
    {
        if (!$source instanceof GitSource) {
            return null;
        }

        return $this->repository->findOneBy([
            'userId' => $source->getUserId(),
            'hostUrl' => $source->getHostUrl(),
            'path' => $source->getPath(),
        ]);
    }
}
