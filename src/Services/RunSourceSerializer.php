<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\RunSource;
use App\Exception\SourceRepositoryCreationException;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnparseableSourceFileException;
use App\Exception\UnserializableSourceException;
use App\Services\SourceRepository\Factory\Factory;
use App\Services\SourceRepository\Serializer;
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
        private Factory $sourceRepositoryFactory,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws UnserializableSourceException
     * @throws SourceRepositoryCreationException
     * @throws UnparseableSourceFileException
     * @throws SourceRepositoryReaderNotFoundException
     */
    public function write(RunSource $target): ?string
    {
        $source = $target->getParent();
        if (null === $source) {
            return null;
        }

        $sourceRepository = $this->sourceRepositoryFactory->create($source, $target->getParameters());
        if (null === $sourceRepository) {
            throw new UnserializableSourceException($source);
        }

        $targetPath = $target->getDirectoryPath() . '/' . self::SERIALIZED_FILENAME;
        $this->runSourceWriter->write($targetPath, $this->serializer->serialize($sourceRepository));
        $this->sourceRepositoryFactory->remove($sourceRepository);

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
