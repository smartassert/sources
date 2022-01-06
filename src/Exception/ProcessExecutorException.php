<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;

class ProcessExecutorException extends \Exception
{
    private const EXCEPTION_TYPE_TO_CODE_MAP = [
        RuntimeException::class => 100,
        ProcessTimedOutException::class => 200,
        ProcessSignaledException::class => 300,
        LogicException::class => 400,
    ];

    private const EXCEPTION_TYPE_TO_MESSAGE_MAP = [
        RuntimeException::class => 'Process cannot be started or is already running',
        ProcessTimedOutException::class => 'Process timed out',
        ProcessSignaledException::class => 'Process stopped after receiving signal',
        LogicException::class => 'Callback was provided and output has been disabled',
    ];

    public function __construct(
        private ExceptionInterface $previous
    ) {
        parent::__construct(
            self::EXCEPTION_TYPE_TO_MESSAGE_MAP[$previous::class] ?? 'unknown',
            self::EXCEPTION_TYPE_TO_CODE_MAP[$previous::class] ?? 0,
            $previous
        );
    }

    public function getException(): ExceptionInterface
    {
        return $this->previous;
    }
}
