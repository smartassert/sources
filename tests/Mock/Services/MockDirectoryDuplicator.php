<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\FileLocatorInterface;
use App\Services\DirectoryDuplicator;
use Mockery\MockInterface;

class MockDirectoryDuplicator
{
    private DirectoryDuplicator $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(DirectoryDuplicator::class);
    }

    public function getMock(): DirectoryDuplicator
    {
        return $this->mock;
    }

    public function withDuplicateCallThrowingException(\Exception $exception): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('duplicate')
            ->withArgs(function (FileLocatorInterface $source, FileLocatorInterface $target): bool {
                return true;
            })
            ->andThrow($exception)
        ;

        return $this;
    }
}
