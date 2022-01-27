<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\ProcessOutput;
use App\Services\GitRepositoryCloner;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class MockGitRepositoryCloner
{
    private GitRepositoryCloner $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(GitRepositoryCloner::class);
    }

    public function getMock(): GitRepositoryCloner
    {
        return $this->mock;
    }

    public function withCloneCall(string $url, ProcessOutput|\Exception $outcome, string &$expectedLocalPath): self
    {
        if (false === $this->mock instanceof MockInterface) {
            return $this;
        }

        $expectation = $this->mock
            ->shouldReceive('clone')
            ->withArgs(function (string $repositoryUrl, string $localPath) use ($url, &$expectedLocalPath): bool {
                TestCase::assertSame($url, $repositoryUrl);
                $expectedLocalPath = $localPath;

                return true;
            });

        if ($outcome instanceof ProcessOutput) {
            $expectation->andReturn($outcome);
        } else {
            $expectation->andThrow($outcome);
        }

        return $this;
    }
}
