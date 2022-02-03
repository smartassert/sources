<?php

declare(strict_types=1);

namespace App\Exception\File;

interface PathExceptionInterface extends FileExceptionInterface
{
    public function getPath(): string;

    public function getContext(): ?string;

    public function withContext(string $context): PathExceptionInterface;
}
