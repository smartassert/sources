<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\RunSource;

class RunSourceResolver extends AbstractSingleSourceTypeResolver
{
    protected function getSourceClassName(): string
    {
        return RunSource::class;
    }
}
