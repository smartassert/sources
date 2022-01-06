<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\ProcessExecutorException;
use App\Model\ProcessOutput;
use App\Services\Process\Executor;

class GitRepositoryCloner
{
    public function __construct(
        private Executor $processExecutor
    ) {
    }

    /**
     * @throws ProcessExecutorException
     */
    public function clone(string $url, string $path): ProcessOutput
    {
        return $this->processExecutor->execute(
            'git clone --no-checkout %url% %path%',
            [
                '%url%' => $url,
                '%path' => $path,
            ]
        );
    }
}
