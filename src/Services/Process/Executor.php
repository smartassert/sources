<?php

declare(strict_types=1);

namespace App\Services\Process;

use App\Exception\ProcessExecutorException;
use App\Model\CommandDefinition\Definition;
use App\Model\ProcessOutput;
use Symfony\Component\Process\Exception\ExceptionInterface;

class Executor
{
    public function __construct(
        private Factory $factory,
    ) {
    }

    /**
     * @throws ProcessExecutorException
     */
    public function execute(Definition $commandDefinition, ?string $cwd = null): ProcessOutput
    {
        $process = $this->factory->create((string) $commandDefinition, $cwd);

        try {
            $exitCode = $process->run();
        } catch (ExceptionInterface $exception) {
            throw new ProcessExecutorException($exception);
        }

        return new ProcessOutput($exitCode, $process->getOutput(), $process->getErrorOutput());
    }
}
