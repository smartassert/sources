<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\Process;

use App\Exception\ProcessExecutorException;
use App\Model\CommandDefinition\Definition;
use App\Model\ProcessOutput;
use App\Services\Process\Executor;
use App\Services\Process\Factory;
use App\Tests\Mock\Services\Process\MockFactory;
use App\Tests\Mock\Symfony\Component\Process\MockProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\RuntimeException;
use webignition\ObjectReflector\ObjectReflector;

class ExecutorTest extends TestCase
{
    private Executor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executor = new Executor(new Factory());
    }

    public function testExecuteThrowsException(): void
    {
        $command = './command';

        $symfonyProcessException = new RuntimeException();
        $process = (new MockProcess())
            ->withRunCallThrowingException($symfonyProcessException)
            ->getMock()
        ;

        $factory = (new MockFactory())
            ->withCreateCall($command, null, $process)
            ->getMock()
        ;

        $this->setProcessFactoryOnExecutor($factory);

        $this->expectExceptionObject(
            new ProcessExecutorException(
                $symfonyProcessException
            )
        );

        $this->executor->execute(new Definition($command));
    }

    /**
     * @dataProvider executeSuccessDataProvider
     */
    public function testExecuteSuccess(
        Definition $commandDefinition,
        ?string $cwd,
        Factory $factory,
        ProcessOutput $expectedOutput
    ): void {
        $this->setProcessFactoryOnExecutor($factory);

        self::assertEquals($expectedOutput, $this->executor->execute($commandDefinition, $cwd));
    }

    /**
     * @return array<mixed>
     */
    public static function executeSuccessDataProvider(): array
    {
        return [
            'command exits with no errors' => [
                'commandDefinition' => new Definition('./command'),
                'cwd' => null,
                'factory' => (new MockFactory())
                    ->withCreateCall(
                        './command',
                        null,
                        (new MockProcess())
                            ->withRunCall(0)
                            ->withGetOutputCall('process output')
                            ->withGetErrorOutputCall('')
                            ->getMock()
                    )
                    ->getMock(),
                'expectedOutput' => new ProcessOutput(0, 'process output', '')
            ],
            'command with cwd exits with no errors' => [
                'commandDefinition' => new Definition('./command'),
                'cwd' => '/path/to/directory',
                'factory' => (new MockFactory())
                    ->withCreateCall(
                        './command',
                        '/path/to/directory',
                        (new MockProcess())
                            ->withRunCall(0)
                            ->withGetOutputCall('process output')
                            ->withGetErrorOutputCall('')
                            ->getMock()
                    )
                    ->getMock(),
                'expectedOutput' => new ProcessOutput(0, 'process output', '')
            ],
            'command exits with error' => [
                'commandDefinition' => new Definition('./command'),
                'cwd' => null,
                'factory' => (new MockFactory())
                    ->withCreateCall(
                        './command',
                        null,
                        (new MockProcess())
                            ->withRunCall(1)
                            ->withGetOutputCall('process output')
                            ->withGetErrorOutputCall('process error output')
                            ->getMock()
                    )
                    ->getMock(),
                'expectedOutput' => new ProcessOutput(1, 'process output', 'process error output')
            ],
        ];
    }

    private function setProcessFactoryOnExecutor(Factory $factory): void
    {
        ObjectReflector::setProperty(
            $this->executor,
            Executor::class,
            'factory',
            $factory
        );
    }
}
