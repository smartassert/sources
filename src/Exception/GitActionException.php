<?php

declare(strict_types=1);

namespace App\Exception;

class GitActionException extends \Exception
{
    public const ACTION_CLONE = 'clone';
    public const ACTION_CHECKOUT = 'checkout';

    /**
     * @param self::ACTION_* $action
     */
    public function __construct(
        private string $action,
        string $message,
        private ?ProcessExecutorException $processExecutorException = null
    ) {
        parent::__construct($message, 0, $processExecutorException);
    }

    public static function createFromCloneErrorOutput(string $errorOutput): self
    {
        $lines = explode("\n", trim($errorOutput));
        array_shift($lines);

        return new GitActionException(self::ACTION_CLONE, implode("\n", $lines));
    }

    public static function createFromCheckoutErrorOutput(string $errorOutput): self
    {
        return new GitActionException(self::ACTION_CHECKOUT, $errorOutput);
    }

    /**
     * @param self::ACTION_* $action
     */
    public static function createForProcessException(string $action, ProcessExecutorException $exception): self
    {
        return new GitActionException($action, 'Git ' . $action . ' process failed', $exception);
    }

    /**
     * @return self::ACTION_*
     */
    public function getAction(): string
    {
        return $this->action;
    }

    public function getProcessExecutorException(): ?ProcessExecutorException
    {
        return $this->processExecutorException;
    }
}
