<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\FileSource;
use App\Entity\SourceInterface;
use App\Model\SourceRepositoryInterface;

class FileSourceHandler implements CreatorInterface
{
    public function createsFor(SourceInterface $source): bool
    {
        return $source instanceof FileSource;
    }

    public function create(SourceInterface $source, array $parameters): ?SourceRepositoryInterface
    {
        return $source instanceof FileSource ? $source : null;
    }
}
