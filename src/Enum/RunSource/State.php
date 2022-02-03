<?php

declare(strict_types=1);

namespace App\Enum\RunSource;

enum State: string
{
    case REQUESTED = 'requested';
    case PREPARING_RUNNING = 'preparing/running';
    case PREPARING_HALTED = 'preparing/halted';
    case FAILED = 'failed';
    case PREPARED = 'prepared';
}
