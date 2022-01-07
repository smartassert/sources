<?php

declare(strict_types=1);

namespace App\Exception\FileStore;

trait GetPathTrait
{
    public function getPath(): string
    {
        return $this->path;
    }
}
