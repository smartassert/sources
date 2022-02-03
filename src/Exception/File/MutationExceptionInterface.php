<?php

declare(strict_types=1);

namespace App\Exception\File;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

interface MutationExceptionInterface extends FileExceptionInterface
{
    public function getIOException(): IOExceptionInterface;
}
