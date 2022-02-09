<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;
use App\Entity\SourceInterface;

class FileSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return FileSource::class === $type;
    }

    protected function doYield(?SourceInterface $source): ?FileSource
    {
        return $source instanceof FileSource ? $source : null;
    }
}
