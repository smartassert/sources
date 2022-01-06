<?php

declare(strict_types=1);

namespace App\Services\Process;

use Symfony\Component\Process\Process;

class Factory
{
    public function create(string $command): Process
    {
        return Process::fromShellCommandline($command);
    }
}
