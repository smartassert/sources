<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;

class FileSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return FileSource::class === $type;
    }
}
