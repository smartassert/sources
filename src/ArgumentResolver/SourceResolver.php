<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;
use App\Repository\RunSourceRepository;

class SourceResolver extends AbstractSourceResolver
{
    public function __construct(
        private readonly FileSourceRepository $fileSourceRepository,
        private readonly GitSourceRepository $gitSourceRepository,
        private readonly RunSourceRepository $runSourceRepository,
    ) {
    }

    protected function supportsArgumentType(string $type): bool
    {
        return SourceInterface::class === $type;
    }

    protected function find(string $id): null|FileSource|GitSource|RunSource
    {
        $source = $this->fileSourceRepository->find($id);
        if (null !== $source) {
            return $source;
        }

        $source = $this->gitSourceRepository->find($id);
        if (null !== $source) {
            return $source;
        }

        return $this->runSourceRepository->find($id);
    }
}
