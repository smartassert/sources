<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceTypeInterface
{
    public const TYPE_GIT = 'git';
    public const TYPE_LOCAL = 'local';
    public const TYPE_RUN = 'run';
}
