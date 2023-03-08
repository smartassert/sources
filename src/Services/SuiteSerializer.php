<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\SerializedSuite;
use App\Exception\SourceRepositoryCreationException;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnserializableSourceException;
use App\Services\SourceRepository\Factory\Factory;
use App\Services\SourceRepository\Serializer;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;

class SuiteSerializer
{
    public const SERIALIZED_FILENAME = 'source.yaml';

    public function __construct(
        private readonly Serializer $serializer,
        private readonly FilesystemReader $serializedSuiteReader,
        private readonly FilesystemWriter $serializedSuiteWriter,
        private readonly Factory $sourceRepositoryFactory,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws UnserializableSourceException
     * @throws SourceRepositoryCreationException
     * @throws SourceRepositoryReaderNotFoundException
     * @throws SerializeException
     */
    public function write(SerializedSuite $serializedSuite): ?string
    {
        $suite = $serializedSuite->suite;
        $source = $suite->getSource();

        $sourceRepository = $this->sourceRepositoryFactory->create($source, $serializedSuite->getParameters());
        if (null === $sourceRepository) {
            throw new UnserializableSourceException($source);
        }

        $targetPath = $serializedSuite->getDirectoryPath() . '/' . self::SERIALIZED_FILENAME;
        $this->serializedSuiteWriter->write(
            $targetPath,
            $this->serializer->serialize(
                $sourceRepository,
                $suite->getTests(),
            )
        );
        $this->sourceRepositoryFactory->remove($sourceRepository);

        return $targetPath;
    }

    /**
     * @throws FilesystemException
     */
    public function read(SerializedSuite $serializedSuite): string
    {
        return trim($this->serializedSuiteReader->read(
            $serializedSuite->getDirectoryPath() . '/' . self::SERIALIZED_FILENAME
        ));
    }
}
