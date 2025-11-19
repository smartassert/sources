<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\CommandDefinition\Definition;
use App\Model\CommandDefinition\Option;
use App\Model\ProcessOutput;
use App\Services\EntityIdFactory;
use App\Services\GitRepositoryCloner;
use App\Tests\Mock\Services\Process\MockExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GitRepositoryClonerTest extends WebTestCase
{
    public function testClone(): void
    {
        $url = 'https://user:password@example.com/repository.git';
        $path = '/' . (new EntityIdFactory())->create() . '/' . (new EntityIdFactory())->create();

        $expectedCommandDefinition = (new Definition('git clone'))
            ->withOptions([
                Option::createLong('no-checkout'),
            ])->withArguments([$url, $path])
        ;

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
