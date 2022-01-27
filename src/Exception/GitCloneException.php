<?php

declare(strict_types=1);

namespace App\Exception;

class GitCloneException extends \Exception
{
    public function __construct(
        string $message,
        private ?ProcessExecutorException $processExecutorException = null
    ) {
        parent::__construct($message, 0, $processExecutorException);
    }

    public static function createFromErrorOutput(string $errorOutput): GitCloneException
    {
        $lines = explode("\n", trim($errorOutput));
        array_shift($lines);

        return new GitCloneException(implode("\n", $lines));
    }

    public function getProcessExecutorException(): ?ProcessExecutorException
    {
        return $this->processExecutorException;
    }
}
