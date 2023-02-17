<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\GitSource;
use App\Repository\GitSourceRepository;

class GitSourceResolver extends AbstractSingleSourceTypeResolver
{
    public function __construct(
        private readonly GitSourceRepository $repository,
    ) {
    }

    protected function find(string $id): ?GitSource
    {
        return $this->repository->find($id);
    }

    protected function getSourceClassName(): string
    {
        return GitSource::class;
    }
}
