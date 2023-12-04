<?php

declare(strict_types=1);

namespace App\ErrorResponse;

interface ErrorInterface
{
    /**
     * @return non-empty-string
     */
    public function getClass(): string;

    /**
     * @return ?non-empty-string
     */
    public function getType(): ?string;
}