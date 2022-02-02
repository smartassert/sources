<?php

declare(strict_types=1);

namespace App\Enum\RunSource;

enum State: string
{
    case REQUESTED = 'requested';
    case PREPARING = 'preparing';
    case FAILED = 'failed';
    case PREPARED = 'prepared';
}
