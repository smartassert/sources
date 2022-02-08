<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\OriginSourceInterface;
use App\Entity\SourceInterface;

class OriginSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return OriginSourceInterface::class === $type;
    }

    protected function doYield(?SourceInterface $source): ?OriginSourceInterface
    {
        return $source instanceof OriginSourceInterface ? $source : null;
    }
}
