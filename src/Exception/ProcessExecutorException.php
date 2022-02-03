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
    /**
     * throw new RuntimeException(sprintf('The provided cwd "%s" does not exist.', $this->cwd));
     * throw new RuntimeException('Unable to launch a new process.');.
     */
    private const EXCEPTION_TYPE_TO_CODE_MAP = [
        ProcessTimedOutException::class => 200,
        ProcessSignaledException::class => 300,
        LogicException::class => 400,
    ];

    private const EXCEPTION_TYPE_TO_MESSAGE_MAP = [
        ProcessTimedOutException::class => 'Process timed out',
        ProcessSignaledException::class => 'Process stopped after receiving signal',
        LogicException::class => 'Callback was provided and output has been disabled',
    ];

    private const RUNTIME_EXCEPTION_CODE_MAP = [
        'Unable to launch' => 100,
        'The provided cwd' => 101,
    ];

    public function __construct(
        private ExceptionInterface $previous
    ) {
        parent::__construct(
            $this->getExceptionMessage($previous),
            $this->getExceptionCode($previous),
            $previous
        );
    }

    public function getException(): ExceptionInterface
    {
        return $this->previous;
    }

    private function getExceptionMessage(\Throwable $exception): string
    {
        $code = self::EXCEPTION_TYPE_TO_MESSAGE_MAP[$exception::class] ?? null;
        if (is_string($code)) {
            return $code;
        }

        if ($exception instanceof RuntimeException) {
            return $exception->getMessage();
        }

        return 'unknown';
    }

    private function getExceptionCode(\Throwable $exception): int
    {
        $code = self::EXCEPTION_TYPE_TO_CODE_MAP[$exception::class] ?? null;
        if (is_int($code)) {
            return $code;
        }

        if (RuntimeException::class === $exception::class) {
            $runtimeExceptionMessage = $exception->getMessage();

            foreach (self::RUNTIME_EXCEPTION_CODE_MAP as $messageStartsWith => $exceptionCode) {
                if (str_starts_with($runtimeExceptionMessage, $messageStartsWith)) {
                    return $exceptionCode;
                }
            }
        }

        return 0;
    }
}
