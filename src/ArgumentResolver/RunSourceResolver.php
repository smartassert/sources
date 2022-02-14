<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\RunSource;

class RunSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return RunSource::class === $type;
    }

    protected function getExpectedInstanceClassName(): string
    {
        return RunSource::class;
    }
}
