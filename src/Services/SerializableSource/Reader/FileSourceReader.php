<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Reader;

use App\Entity\FileSource;
use App\Model\SerializableSourceInterface;
use League\Flysystem\FilesystemReader;

class FileSourceReader implements ReaderInterface
{
    public function __construct(
        private FilesystemReader $reader,
    ) {
    }

    public function handles(SerializableSourceInterface $serializableSource): bool
    {
        return $serializableSource instanceof FileSource;
    }

    public function getReader(): FilesystemReader
    {
        return $this->reader;
    }
}
