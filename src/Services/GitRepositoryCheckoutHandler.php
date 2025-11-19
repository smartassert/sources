<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\GitActionException;
use App\Exception\ProcessExecutorException;
use App\Model\CommandDefinition\Definition;
use App\Model\ProcessOutput;
use App\Services\Process\Executor;

class GitRepositoryCheckoutHandler
{
    public function __construct(
        private Executor $processExecutor
    ) {}

    /**
     * @throws GitActionException
     */
    public function checkout(string $path, ?string $ref = null): ProcessOutput
    {
        try {
            return $this->processExecutor->execute(
                (new Definition('git checkout'))
                    ->withArguments([$ref]),
                $path
            );
        } catch (ProcessExecutorException $e) {
            throw GitActionException::createForProcessException(GitActionException::ACTION_CHECKOUT, $e);
        }
    }
}
