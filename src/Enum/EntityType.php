<?php

declare(strict_types=1);

namespace App\Enum;

enum EntityType: string
{
    case GIT_SOURCE = 'git-source';
    case FILE_SOURCE = 'file-source';
    case SERIALIZED_SUITE = 'serialized_suite';
    case SUITE = 'suite';
}
