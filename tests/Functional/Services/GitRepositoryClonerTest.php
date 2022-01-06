<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\EntityId;
use App\Model\ProcessOutput;
use App\Services\GitRepositoryCloner;
use App\Tests\Mock\Services\Process\MockExecutor;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class GitRepositoryClonerTest extends WebTestCase
{
    private GitRepositoryCloner $gitRepositoryCloner;

    protected function setUp(): void
    {
        parent::setUp();

        $gitRepositoryCloner = self::getContainer()->get(GitRepositoryCloner::class);
        \assert($gitRepositoryCloner instanceof GitRepositoryCloner);
        $this->gitRepositoryCloner = $gitRepositoryCloner;
    }

    public function testClone(): void
    {
        $url = 'https://user:password@example.com/repository.git';
        $path = UserId::create() . '/' . EntityId::create();

        $expectedExecutorCommand = 'git clone --no-checkout %url% %path%';
        $expectedExecutorArguments = [
            '%url%' => $url,
            '%path' => $path,
        ];
        $executorProcessOutput = new ProcessOutput(0, '', '');

        $executor = (new MockExecutor())
            ->withExecuteCall($expectedExecutorCommand, $expectedExecutorArguments, $executorProcessOutput)
            ->getMock()
        ;

        ObjectReflector::setProperty(
            $this->gitRepositoryCloner,
            $this->gitRepositoryCloner::class,
            'processExecutor',
            $executor
        );

        self::assertEquals(
            $executorProcessOutput,
            $this->gitRepositoryCloner->clone($url, $path)
        );
    }
}
