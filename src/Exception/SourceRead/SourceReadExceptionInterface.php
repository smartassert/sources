<?php

declare(strict_types=1);

namespace App\Exception\SourceRead;

use Symfony\Component\Finder\SplFileInfo;

interface SourceReadExceptionInterface extends \Throwable
{
    public function getSourceFile(): SplFileInfo;
}
