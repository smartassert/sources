<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Reader;

use App\Exception\SerializableSourceReaderNotFoundException;
use App\Model\SerializableSourceInterface;
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
     * @throws SerializableSourceReaderNotFoundException
     */
    public function find(SerializableSourceInterface $serializableSource): FilesystemReader
    {
        foreach ($this->readers as $reader) {
            if ($reader instanceof ReaderInterface && $reader->handles($serializableSource)) {
                return $reader->getReader();
            }
        }

        throw new SerializableSourceReaderNotFoundException($serializableSource);
    }
}
