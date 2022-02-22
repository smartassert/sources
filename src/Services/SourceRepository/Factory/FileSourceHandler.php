<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\FileSource;
use App\Entity\OriginSourceInterface;
use App\Model\SourceRepositoryInterface;

class FileSourceHandler implements CreatorInterface
{
    public function createsFor(OriginSourceInterface $origin): bool
    {
        return $origin instanceof FileSource;
    }

    public function create(OriginSourceInterface $origin, array $parameters): ?SourceRepositoryInterface
    {
        return $origin instanceof FileSource ? $origin : null;
    }
}
