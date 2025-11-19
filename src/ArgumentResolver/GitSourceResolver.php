<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\GitSourceInterface;

class GitSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return GitSourceInterface::class === $type;
    }
}
