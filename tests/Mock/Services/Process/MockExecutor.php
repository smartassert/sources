<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services\Process;

use App\Model\ProcessOutput;
use App\Services\Process\Executor;
use Mockery\MockInterface;

class MockExecutor
{
    private Executor $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(Executor::class);
    }

    public function getMock(): Executor
    {
        return $this->mock;
    }

    /**
     * @param array<string, string> $arguments
     */
    public function withExecuteCall(string $command, array $arguments, ProcessOutput $output): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('execute')
            ->with($command, $arguments)
            ->andReturn($output)
        ;

        return $this;
    }
}
