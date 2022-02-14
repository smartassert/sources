<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceInterface;

class SourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return SourceInterface::class === $type;
    }

    protected function getExpectedInstanceClassName(): string
    {
        return SourceInterface::class;
    }
}
