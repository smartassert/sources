<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\OriginSourceInterface;

class OriginSourceResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return OriginSourceInterface::class === $type;
    }

    protected function getExpectedInstanceClassName(): string
    {
        return OriginSourceInterface::class;
    }
}
