<?php

declare(strict_types=1);

namespace App\Model;

interface SerializableSourceInterface
{
    public function getSerializableSourcePath(): string;

    public function getFilePath(): string;
}
