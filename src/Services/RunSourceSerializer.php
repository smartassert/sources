<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\RunSource;
use App\Exception\SerializableSourceCreationException;
use App\Exception\SerializableSourceReaderNotFoundException;
use App\Exception\UnparseableSourceFileException;
use App\Exception\UnserializableSourceException;
use App\Services\SerializableSource\Factory\Factory;
use App\Services\SerializableSource\Serializer;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;

class RunSourceSerializer
{
    public const SERIALIZED_FILENAME = 'source.yaml';

    public function __construct(
        private Serializer $serializer,
        private FilesystemReader $runSourceReader,
        private FilesystemWriter $runSourceWriter,
        private Factory $serializableSourceFactory,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws UnserializableSourceException
     * @throws SerializableSourceCreationException
     * @throws UnparseableSourceFileException
     * @throws SerializableSourceReaderNotFoundException
     */
    public function write(RunSource $target): ?string
    {
        $source = $target->getParent();
        if (null === $source) {
            return null;
        }

        $serializableSource = $this->serializableSourceFactory->create($source, $target->getParameters());
        if (null === $serializableSource) {
            throw new UnserializableSourceException($source);
        }

        $targetPath = $target->getDirectoryPath() . '/' . self::SERIALIZED_FILENAME;
        $this->runSourceWriter->write($targetPath, $this->serializer->serialize($serializableSource));
        $this->serializableSourceFactory->remove($serializableSource);

        return $targetPath;
    }

    /**
     * @throws FilesystemException
     */
    public function read(RunSource $runSource): string
    {
        return trim($this->runSourceReader->read($runSource->getDirectoryPath() . '/' . self::SERIALIZED_FILENAME));
    }
}
