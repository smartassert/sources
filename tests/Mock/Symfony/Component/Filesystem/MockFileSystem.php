<?php

declare(strict_types=1);

namespace App\Tests\Mock\Symfony\Component\Filesystem;

use Mockery\MockInterface;
use Symfony\Component\Filesystem\Filesystem;

class MockFileSystem
{
    private Filesystem $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(Filesystem::class);
    }

    public function getMock(): Filesystem
    {
        return $this->mock;
    }

    public function withExistsCall(string $path, bool $result): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('exists')
            ->with($path)
            ->andReturn($result)
        ;

        return $this;
    }

    public function withRemoveCall(string $path): self
    {
        return $this->withDirectoryCall('remove', $path);
    }

    public function withMkdirCall(string $path): self
    {
        return $this->withDirectoryCall('mkdir', $path);
    }

    public function withRemoveCallThrowingException(string $path, \Exception $exception): self
    {
        return $this->withDirectoryCallThrowingException('remove', $path, $exception);
    }

    public function withMkdirCallThrowingException(string $path, \Exception $exception): self
    {
        return $this->withDirectoryCallThrowingException('mkdir', $path, $exception);
    }

    public function withMirrorCallThrowingException(string $source, string $target, \Exception $exception): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('mirror')
            ->with($source, $target)
            ->andThrow($exception)
        ;

        return $this;
    }

    public function withoutRemoveCall(): self
    {
        return $this->withoutCall('remove');
    }

    public function withoutMkdirCall(): self
    {
        return $this->withoutCall('mkdir');
    }

    public function withoutMirrorCall(): self
    {
        return $this->withoutCall('mirror');
    }

    private function withoutCall(string $method): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock->shouldNotReceive($method);

        return $this;
    }

    private function withDirectoryCall(string $method, string $path): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive($method)
            ->with($path)
        ;

        return $this;
    }

    private function withDirectoryCallThrowingException(string $method, string $path, \Exception $exception): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive($method)
            ->with($path)
            ->andThrow($exception)
        ;

        return $this;
    }
}
