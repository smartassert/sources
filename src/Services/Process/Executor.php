<?php

declare(strict_types=1);

namespace App\Services\Process;

use App\Exception\ProcessExecutorException;
use App\Model\ProcessOutput;
use Symfony\Component\Process\Exception\ExceptionInterface;

class Executor
{
    public function __construct(
        private CommandBuilder $commandBuilder,
        private Factory $factory,
    ) {
    }

    /**
     * @param array<string, string> $parameters
     *
     * @throws ProcessExecutorException
     */
    public function execute(string $command, array $parameters = [], ?string $cwd = null): ProcessOutput
    {
        $builtCommand = $this->commandBuilder->build($command, $parameters);
        $process = $this->factory->create($builtCommand, $cwd);

        try {
            $exitCode = $process->run();
        } catch (ExceptionInterface $exception) {
            throw new ProcessExecutorException($exception);
        }

        return new ProcessOutput($exitCode, $process->getOutput(), $process->getErrorOutput());
    }
}
