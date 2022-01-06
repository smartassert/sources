<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services\Process;

use App\Services\Process\Factory;
use Mockery\MockInterface;
use Symfony\Component\Process\Process;

class MockFactory
{
    private Factory $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(Factory::class);
    }

    public function getMock(): Factory
    {
        return $this->mock;
    }

    public function withCreateCall(string $command, Process $process): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('create')
            ->with($command)
            ->andReturn($process)
        ;

        return $this;
    }
}
