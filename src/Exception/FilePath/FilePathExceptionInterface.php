<?php

declare(strict_types=1);

namespace App\Exception\FilePath;

interface FilePathExceptionInterface extends \Throwable
{
    public function getPath(): string;
}
