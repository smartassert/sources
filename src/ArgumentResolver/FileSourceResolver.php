<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;

class FileSourceResolver extends AbstractSingleSourceTypeResolver
{
    protected function getSourceClassName(): string
    {
        return FileSource::class;
    }
}
