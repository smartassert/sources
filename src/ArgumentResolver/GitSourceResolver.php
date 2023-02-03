<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\GitSource;

class GitSourceResolver extends AbstractSingleSourceTypeResolver
{
    protected function getSourceClassName(): string
    {
        return GitSource::class;
    }
}
