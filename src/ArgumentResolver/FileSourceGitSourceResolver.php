<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;

class FileSourceGitSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return str_contains($type, FileSource::class) || str_contains($type, GitSource::class);
    }

    protected function doYield(?SourceInterface $source): ?SourceInterface
    {
        return $source instanceof FileSource || $source instanceof GitSource ? $source : null;
    }
}
