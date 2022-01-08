<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\ProcessExecutorException;
use App\Tests\Exception\UnknownSymfonyProcessException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ProcessExecutorExceptionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider getPropertiesDataProvider
     */
    public function testGetProperties(
        ProcessExecutorException $exception,
        string $expectedMessage,
        int $expectedCode,
        ExceptionInterface $expectedException,
    ): void {
        self::assertSame($expectedMessage, $exception->getMessage());
        self::assertSame($expectedCode, $exception->getCode());
        self::assertSame($expectedException, $exception->getException());
    }

    /**
     * @return array<mixed>
     */
    public function getPropertiesDataProvider(): array
    {
        $unableToLaunchRuntimeException = new RuntimeException('Unable to launch a new process');
        $cwdDoesNotExistRuntimeException = new RuntimeException('The provided cwd "/foo" does not exist');

        $timedOutProcess = \Mockery::mock(Process::class);
        $timedOutProcess
            ->shouldReceive('getCommandLine')
            ->andReturn('./timed-out')
        ;

        $timedOutProcess
            ->shouldReceive('getTimeout')
            ->andReturn(60)
        ;

        $processTimedOutException = new ProcessTimedOutException(
            $timedOutProcess,
            ProcessTimedOutException::TYPE_GENERAL
        );

        $signaledProcess = \Mockery::mock(Process::class);
        $signaledProcess
            ->shouldReceive('getTermSignal')
            ->andReturn(9)
        ;

        $processSignaledException = new ProcessSignaledException($signaledProcess);

        $logicException = new LogicException();

        $unknownException = new UnknownSymfonyProcessException();

        return [
            'Unable to launch ' . RuntimeException::class => [
                'exception' => new ProcessExecutorException($unableToLaunchRuntimeException),
                'expectedMessage' => $unableToLaunchRuntimeException->getMessage(),
                'expectedCode' => 100,
                'expectedException' => $unableToLaunchRuntimeException,
            ],
            'Cwd does not exist ' . RuntimeException::class => [
                'exception' => new ProcessExecutorException($cwdDoesNotExistRuntimeException),
                'expectedMessage' => $cwdDoesNotExistRuntimeException->getMessage(),
                'expectedCode' => 101,
                'expectedException' => $cwdDoesNotExistRuntimeException,
            ],
            ProcessTimedOutException::class => [
                'exception' => new ProcessExecutorException($processTimedOutException),
                'expectedMessage' => 'Process timed out',
                'expectedCode' => 200,
                'expectedException' => $processTimedOutException,
            ],
            ProcessSignaledException::class => [
                'exception' => new ProcessExecutorException($processSignaledException),
                'expectedMessage' => 'Process stopped after receiving signal',
                'expectedCode' => 300,
                'expectedException' => $processSignaledException,
            ],
            LogicException::class => [
                'exception' => new ProcessExecutorException($logicException),
                'expectedMessage' => 'Callback was provided and output has been disabled',
                'expectedCode' => 400,
                'expectedException' => $logicException,
            ],
            'unknown' => [
                'exception' => new ProcessExecutorException($unknownException),
                'expectedMessage' => 'unknown',
                'expectedCode' => 0,
                'expectedException' => $unknownException,
            ],
        ];
    }
}
