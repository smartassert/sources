<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;
use App\Repository\FileSourceRepository;

class FileSourceResolver extends AbstractSourceResolver
{
    public function __construct(
        private readonly FileSourceRepository $repository,
    ) {
    }

    protected function find(string $id): ?FileSource
    {
        return $this->repository->find($id);
    }

    protected function supportsArgumentType(string $type): bool
    {
        return FileSource::class === $type;
    }
}
