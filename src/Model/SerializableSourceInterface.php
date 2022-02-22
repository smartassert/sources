<?php

declare(strict_types=1);

namespace App\Model;

interface SerializableSourceInterface
{
    public function getSerializablePath(): string;

    public function getDirectoryPath(): string;
}
