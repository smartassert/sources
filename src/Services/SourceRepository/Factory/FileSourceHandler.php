<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\FileSource;
use App\Entity\SourceOriginInterface;
use App\Model\SourceRepositoryInterface;

class FileSourceHandler implements CreatorInterface
{
    public function createsFor(SourceOriginInterface $source): bool
    {
        return $source instanceof FileSource;
    }

    public function create(SourceOriginInterface $source, array $parameters): ?SourceRepositoryInterface
    {
        return $source instanceof FileSource ? $source : null;
    }
}
