<?php

declare(strict_types=1);

namespace App\Exception\SourceRead;

interface SourceReadExceptionInterface extends \Throwable
{
    public function getSourceFile(): string;
}
