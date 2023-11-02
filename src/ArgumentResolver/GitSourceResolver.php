<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\GitSource;

class GitSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return GitSource::class === $type;
    }
}
