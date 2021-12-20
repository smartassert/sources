<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceTypeInterface
{
    public const TYPE_GIT = 'git';
    public const TYPE_FILE = 'file';
    public const TYPE_RUN = 'run';
}
