<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\CommandDefinition;
use App\Model\EntityId;
use App\Model\ProcessOutput;
use App\Services\GitRepositoryCloner;
use App\Tests\Mock\Services\Process\MockExecutor;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GitRepositoryClonerTest extends WebTestCase
{
    public function testClone(): void
    {
        $url = 'https://user:password@example.com/repository.git';
        $path = UserId::create() . '/' . EntityId::create();

        $expectedCommandDefinition = new CommandDefinition('git clone --no-checkout %url% %path%', [
            '%url%' => $url,
            '%path%' => $path,
        ]);

        $executorProcessOutput = new ProcessOutput(0, '', '');

        $executor = (new MockExecutor())
            ->withExecuteCall($expectedCommandDefinition, $executorProcessOutput)
            ->getMock()
        ;

        $gitRepositoryCloner = new GitRepositoryCloner($executor);

        self::assertEquals(
            $executorProcessOutput,
            $gitRepositoryCloner->clone($url, $path)
        );
    }
}
