<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\RunSource;
use App\Entity\SourceInterface;

class RunSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return RunSource::class === $type;
    }

    protected function doYield(?SourceInterface $source): ?RunSource
    {
        return $source instanceof RunSource ? $source : null;
    }
}
