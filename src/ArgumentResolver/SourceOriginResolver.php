<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceOriginInterface;

class SourceOriginResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return SourceOriginInterface::class === $type;
    }

    protected function getExpectedInstanceClassName(): string
    {
        return SourceOriginInterface::class;
    }
}
