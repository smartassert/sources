<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

abstract class AbstractSingleSourceTypeResolver extends AbstractSourceResolver
{
    protected function supportsArgumentType(string $type): bool
    {
        return $this->getSourceClassName() === $type;
    }

    /**
     * @return class-string
     */
    abstract protected function getSourceClassName(): string;
}
