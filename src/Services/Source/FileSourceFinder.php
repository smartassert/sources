<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\SourceInterface;
use App\Repository\FileSourceRepository;

class FileSourceFinder implements TypeFinderInterface
{
    public function __construct(
        private FileSourceRepository $repository
    ) {
    }

    public function supports(string $type): bool
    {
        return SourceInterface::TYPE_FILE === $type;
    }

    public function find(SourceInterface $source): ?FileSource
    {
        if (!$source instanceof FileSource) {
            return null;
        }

        return $this->repository->findOneBy([
            'userId' => $source->getUserId(),
            'label' => $source->getLabel(),
        ]);
    }
}
