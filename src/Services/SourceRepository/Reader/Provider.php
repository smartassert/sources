<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Model\SourceRepositoryInterface;
use League\Flysystem\FilesystemReader;

class Provider
{
    /**
     * @param ReaderInterface[] $readers
     */
    public function __construct(
        private array $readers,
    ) {
    }

    /**
     * @throws SourceRepositoryReaderNotFoundException
     */
    public function find(SourceRepositoryInterface $sourceRepository): FilesystemReader
    {
        foreach ($this->readers as $reader) {
            if ($reader instanceof ReaderInterface && $reader->handles($sourceRepository)) {
                return $reader->getReader();
            }
        }

        throw new SourceRepositoryReaderNotFoundException($sourceRepository);
    }
}
