<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\GitRepositoryCheckoutHandler;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class MockGitRepositoryCheckoutHandler
{
    private GitRepositoryCheckoutHandler $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(GitRepositoryCheckoutHandler::class);
    }

    public function getMock(): GitRepositoryCheckoutHandler
    {
        return $this->mock;
    }

    /**
     * @param \Closure(string): \App\Model\ProcessOutput|\Exception $outcome
     */
    public function withCheckoutCall(
        string &$expectedGitRepositoryAbsolutePath,
        string $expectedRef,
        \Closure|\Exception $outcome
    ): self {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $expectation = $this->mock
            ->shouldReceive('checkout')
            ->withArgs(function (
                string $passedPath,
                string $passedRef
            ) use (
                &$expectedGitRepositoryAbsolutePath,
                $expectedRef
            ) {
                TestCase::assertSame($expectedGitRepositoryAbsolutePath, $passedPath);
                TestCase::assertSame($expectedRef, $passedRef);

                return true;
            });

        if ($outcome instanceof \Closure) {
            $expectation->andReturnUsing(function () use (&$expectedGitRepositoryAbsolutePath, $outcome) {
                return $outcome($expectedGitRepositoryAbsolutePath);
            });
        } else {
            $expectation->andThrow($outcome);
        }

        return $this;
    }
}
