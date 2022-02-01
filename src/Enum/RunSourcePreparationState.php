<?php

declare(strict_types=1);

namespace App\Enum;

enum RunSourcePreparationState: string
{
    case UNKNOWN = 'unknown';
    case REQUESTED = 'requested';
    case PREPARING = 'preparing';
    case FAILED = 'failed';
    case PREPARED = 'prepared';
}
