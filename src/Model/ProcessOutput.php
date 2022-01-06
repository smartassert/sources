<?php

declare(strict_types=1);

namespace App\Model;

class ProcessOutput
{
    public function __construct(
        private int $exitCode,
        private string $output,
        private string $errorOutput
    ) {
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }
}
