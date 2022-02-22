<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Model\SourceRepositoryInterface;
use League\Flysystem\FilesystemReader;

interface ReaderInterface
{
    public function handles(SourceRepositoryInterface $sourceRepository): bool;

    public function getReader(): FilesystemReader;
}
