<?php

declare(strict_types=1);

namespace App\Tests\Mock\Model;

use App\Model\FileLocatorInterface;
use Mockery\MockInterface;

class MockFileLocator
{
    private FileLocatorInterface $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(FileLocatorInterface::class);
    }

    public function getMock(): FileLocatorInterface
    {
        return $this->mock;
    }

    public function withToStringCall(string $path): self
    {
        return $this->withPathCall('__toString', $path);
    }

    public function withGetPathCall(string $path): self
    {
        return $this->withPathCall('getPath', $path);
    }

    private function withPathCall(string $method, string $path): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive($method)
            ->andReturn($path)
        ;

        return $this;
    }
}
