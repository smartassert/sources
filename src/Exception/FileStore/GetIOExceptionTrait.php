<?php

declare(strict_types=1);

namespace App\Exception\FileStore;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

trait GetIOExceptionTrait
{
    public function getIOException(): IOExceptionInterface
    {
        return $this->IOException;
    }
}
