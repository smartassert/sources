<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\CommandDefinition;
use App\Model\ProcessOutput;
use App\Services\GitRepositoryCheckoutHandler;
use App\Tests\Mock\Services\Process\MockExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GitRepositoryCheckoutHandlerTest extends WebTestCase
{
    /**
     * @dataProvider checkoutDataProvider
     */
    public function testCheckout(
        string $repositoryPath,
        ?string $ref,
        CommandDefinition $expectedCommandDefinition
    ): void {
        $executorProcessOutput = new ProcessOutput(0, '', '');

        $executor = (new MockExecutor())
            ->withExecuteCall($expectedCommandDefinition, $executorProcessOutput)
            ->getMock()
        ;

        $gitRepositoryCheckoutHandler = new GitRepositoryCheckoutHandler($executor);

        self::assertEquals(
            $executorProcessOutput,
            $gitRepositoryCheckoutHandler->checkout($repositoryPath, $ref)
        );
    }

    /**
     * @return array<mixed>
     */
    public function checkoutDataProvider(): array
    {
        return [
            'without ref' => [
                'repositoryPath' => '/path/to/repository',
                'ref' => null,
                'expectedCommandDefinition' => new CommandDefinition('git checkout')
            ],
            'with ref' => [
                'repositoryPath' => '/path/to/repository',
                'ref' => 'ref',
                'expectedCommandDefinition' => new CommandDefinition('git checkout ref')
            ],
        ];
    }
}
