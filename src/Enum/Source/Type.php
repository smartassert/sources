<?php

declare(strict_types=1);

namespace App\Enum\Source;

enum Type: string
{
    case GIT = 'git';
    case FILE = 'file';
}
