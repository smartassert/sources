<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\SerializedSuite;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Exception\SerializedSuiteSourceDoesNotExistException;
use App\Exception\SourceRepositoryCreationException;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnableToWriteSerializedSuiteException;
use App\Services\SourceRepository\Factory\Factory;
use App\Services\SourceRepository\Reader\Provider;
use App\Services\YamlFileCollection\Provider as YamlFileCollectionProvider;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use SmartAssert\WorkerJobSource\JobSourceSerializer;
use SmartAssert\WorkerJobSource\Model\JobSource;
use SmartAssert\WorkerJobSource\Model\Manifest;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;
use Symfony\Component\Yaml\Parser as YamlParser;

class SuiteSerializer
{
    public const SERIALIZED_FILENAME = 'source.yaml';

    public function __construct(
        private readonly FilesystemReader $serializedSuiteReader,
        private readonly FilesystemWriter $serializedSuiteWriter,
        private readonly Factory $sourceRepositoryFactory,
        private readonly Provider $readerProvider,
        private readonly JobSourceSerializer $jobSourceSerializer,
        private readonly YamlParser $yamlParser,
        private readonly DirectoryListingFilter $listingFilter,
    ) {
    }

    /**
     * @throws SourceRepositoryCreationException
     * @throws SourceRepositoryReaderNotFoundException
     * @throws SerializeException
     * @throws NoSourceRepositoryCreatorException
     * @throws UnableToWriteSerializedSuiteException
     */
    public function write(SerializedSuite $serializedSuite): ?string
    {
        $suite = $serializedSuite->suite;
        $source = $suite->getSource();

        $sourceRepository = $this->sourceRepositoryFactory->create($source, $serializedSuite->getParameters());

        $targetPath = $serializedSuite->getDirectoryPath() . '/' . self::SERIALIZED_FILENAME;

        $provider = new YamlFileCollectionProvider(
            $this->yamlParser,
            $this->listingFilter,
            $this->readerProvider->find($sourceRepository),
            $sourceRepository->getRepositoryPath()
        );

        $content = $this->jobSourceSerializer->serialize(new JobSource(new Manifest($suite->getTests()), $provider));

        try {
            $this->serializedSuiteWriter->write($targetPath, $content);
        } catch (\Throwable $e) {
            throw new UnableToWriteSerializedSuiteException($targetPath, $content, $e);
        }

        try {
            $this->sourceRepositoryFactory->remove($sourceRepository);
        } catch (FilesystemException) {
            // Intentionally empty catch block.
            // We don't want to fail hard if the temporary local source directory cannot be removed.
        }

        return $targetPath;
    }

    /**
     * @throws FilesystemException
     * @throws SerializedSuiteSourceDoesNotExistException
     */
    public function read(SerializedSuite $serializedSuite): string
    {
        $path = $serializedSuite->getDirectoryPath() . '/' . self::SERIALIZED_FILENAME;

        if (false === $this->serializedSuiteReader->fileExists($path)) {
            throw new SerializedSuiteSourceDoesNotExistException($serializedSuite);
        }

        return trim($this->serializedSuiteReader->read($path));
    }
}
