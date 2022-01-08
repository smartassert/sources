<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services\Process;

use App\Model\CommandDefinition\Definition;
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

    public function withExecuteCall(Definition $commandDefinition, ProcessOutput $output): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('execute')
            ->withArgs(function (Definition $passedCommandDefinition) use ($commandDefinition) {
                \assert((string) $commandDefinition === (string) $passedCommandDefinition);

                return true;
            })
            ->andReturn($output)
        ;

        return $this;
    }
}
