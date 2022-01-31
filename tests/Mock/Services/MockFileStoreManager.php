<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

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
            ->withArgs(function (string $sourceRelativePath, string $targetRelativePath): bool {
                return true;
            })
            ->andThrow($exception)
        ;

        return $this;
    }

    public function withRemoveCall(string|\Exception $outcome): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $expectation = $this->mock
            ->shouldReceive('remove')
            ->withArgs(function (string $gitRepositoryRelativePath): bool {
                return true;
            });

        if (is_string($outcome)) {
            $expectation->andReturn($outcome);
        } else {
            $expectation->andThrow($outcome);
        }

        return $this;
    }

    public function withCreateCallThrowingException(\Exception $exception): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $this->mock
            ->shouldReceive('create')
            ->withArgs(function (string $gitRepositoryRelativePath): bool {
                return true;
            })
            ->andThrow($exception)
        ;

        return $this;
    }
}
