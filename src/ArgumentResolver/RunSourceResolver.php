<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\RunSource;
use App\Repository\RunSourceRepository;

class RunSourceResolver extends AbstractSingleSourceTypeResolver
{
    public function __construct(
        private readonly RunSourceRepository $repository,
    ) {
    }

    protected function find(string $id): ?RunSource
    {
        return $this->repository->find($id);
    }

    protected function getSourceClassName(): string
    {
        return RunSource::class;
    }
}
