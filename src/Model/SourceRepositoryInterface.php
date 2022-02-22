<?php

declare(strict_types=1);

namespace App\Model;

interface SourceRepositoryInterface
{
    public function getSerializablePath(): string;

    public function getDirectoryPath(): string;
}
