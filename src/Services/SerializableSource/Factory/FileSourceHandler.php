<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Factory;

use App\Entity\FileSource;
use App\Entity\OriginSourceInterface;
use App\Model\SerializableSourceInterface;

class FileSourceHandler implements CreatorInterface
{
    public function createsFor(OriginSourceInterface $origin): bool
    {
        return $origin instanceof FileSource;
    }

    public function create(OriginSourceInterface $origin, array $parameters): ?SerializableSourceInterface
    {
        return $origin instanceof FileSource ? $origin : null;
    }
}
