<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;
use App\Repository\FileSourceRepository;

class FileSourceResolver extends AbstractSingleSourceTypeResolver
{
    public function __construct(
        private readonly FileSourceRepository $fooRepository,
    ) {
    }

    protected function find(string $id): ?FileSource
    {
        return $this->fooRepository->find($id);
    }

    protected function getSourceClassName(): string
    {
        return FileSource::class;
    }
}
