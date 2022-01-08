<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\ProcessExecutorException;
use App\Model\CommandDefinition;
use App\Model\ProcessOutput;
use App\Services\Process\Executor;

class GitRepositoryCheckoutHandler
{
    public function __construct(
        private Executor $processExecutor
    ) {
    }

    /**
     * @throws ProcessExecutorException
     */
    public function checkout(string $repositoryPath, ?string $ref = null): ProcessOutput
    {
        $command = 'git checkout';
        if (null !== $ref) {
            $command .= ' %ref%';
        }

        return $this->processExecutor->execute(
            new CommandDefinition($command, ['%ref%' => (string) $ref]),
            $repositoryPath
        );
    }
}
