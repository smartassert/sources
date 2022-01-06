<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\Process;

use App\Exception\ProcessExecutorException;
use App\Model\ProcessOutput;
use App\Services\Process\CommandBuilder;
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

        $this->executor = new Executor(new CommandBuilder(), new Factory());
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
            ->withCreateCall($command, $process)
            ->getMock()
        ;

        $this->setProcessFactoryOnExecutor($factory);

        $this->expectExceptionObject(
            new ProcessExecutorException(
                $symfonyProcessException
            )
        );

        $this->executor->execute($command, []);
    }

    /**
     * @dataProvider executeSuccessDataProvider
     *
     * @param array<string, string> $parameters
     */
    public function testExecuteSuccess(
        string $command,
        array $parameters,
        Factory $factory,
        ProcessOutput $expectedOutput
    ): void {
        $this->setProcessFactoryOnExecutor($factory);

        self::assertEquals($expectedOutput, $this->executor->execute($command, $parameters));
    }

    /**
     * @return array<mixed>
     */
    public function executeSuccessDataProvider(): array
    {
        return [
            'command exits with no errors' => [
                'command' => './command %param1% %param2%',
                'parameters' => [
                    '%param1%' => 'first parameter',
                    '%param2%' => 'second parameter',
                ],
                'factory' => (new MockFactory())
                    ->withCreateCall(
                        "./command 'first parameter' 'second parameter'",
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
                'command' => './command %param1% %param2%',
                'parameters' => [
                    '%param1%' => 'first parameter',
                    '%param2%' => 'second parameter',
                ],
                'factory' => (new MockFactory())
                    ->withCreateCall(
                        "./command 'first parameter' 'second parameter'",
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
