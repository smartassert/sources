<?php

declare(strict_types=1);

namespace App\Tests\Mock\Symfony\Component\Process;

use Mockery\MockInterface;
use Symfony\Component\Process\Process;

class MockProcess
{
    private Process $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(Process::class);
    }

    public function getMock(): Process
    {
        return $this->mock;
    }

    public function withRunCall(int $exitCode): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('run')
            ->andReturn($exitCode)
        ;

        return $this;
    }

    public function withGetOutputCall(string $output): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getOutput')
            ->andReturn($output)
        ;

        return $this;
    }

    public function withGetErrorOutputCall(string $output): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('getErrorOutput')
            ->andReturn($output)
        ;

        return $this;
    }

    public function withRunCallThrowingException(\Exception $exception): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('run')
            ->andThrow($exception)
        ;

        return $this;
    }
}
