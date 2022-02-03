<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\GitActionException;
use App\Exception\ProcessExecutorException;
use App\Model\CommandDefinition\Definition;
use App\Model\CommandDefinition\Option;
use App\Model\ProcessOutput;
use App\Services\Process\Executor;

class GitRepositoryCloner
{
    public function __construct(
        private Executor $processExecutor
    ) {
    }

    /**
     * @throws GitActionException
     */
    public function clone(string $url, string $path): ProcessOutput
    {
        try {
            return $this->processExecutor->execute(
                (new Definition('git clone'))
                    ->withOptions([
                        Option::createLong('no-checkout'),
                    ])
                    ->withArguments([$url, $path])
            );
        } catch (ProcessExecutorException $e) {
            throw GitActionException::createForProcessException(GitActionException::ACTION_CLONE, $e);
        }
    }
}
