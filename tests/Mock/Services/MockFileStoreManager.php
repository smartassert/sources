<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\FileLocatorInterface;
use App\Services\FileStoreManager;
use Mockery\MockInterface;

class MockFileStoreManager
{
    private FileStoreManager $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(FileStoreManager::class);
    }

    public function getMock(): FileStoreManager
    {
        return $this->mock;
    }

    public function withMirrorCallThrowingException(\Exception $exception): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('mirror')
            ->withArgs(function (FileLocatorInterface $source, FileLocatorInterface $target): bool {
                return true;
            })
            ->andThrow($exception)
        ;

        return $this;
    }
}
