<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\FileSourceInterface;
use App\Entity\SourceInterface;
use App\Model\SourceRepositoryInterface;

class FileSourceHandler implements CreatorInterface
{
    public function createsFor(SourceInterface $source): bool
    {
        return $source instanceof FileSourceInterface;
    }

    public function create(SourceInterface $source, array $parameters): ?SourceRepositoryInterface
    {
        return $source instanceof FileSourceInterface ? $source : null;
    }
}
