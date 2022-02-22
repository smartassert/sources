<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Entity\FileSource;
use App\Model\SourceRepositoryInterface;
use League\Flysystem\FilesystemReader;

class FileSourceReader implements ReaderInterface
{
    public function __construct(
        private FilesystemReader $reader,
    ) {
    }

    public function handles(SourceRepositoryInterface $sourceRepository): bool
    {
        return $sourceRepository instanceof FileSource;
    }

    public function getReader(): FilesystemReader
    {
        return $this->reader;
    }
}
