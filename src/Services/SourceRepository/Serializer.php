<?php

declare(strict_types=1);

namespace App\Services\SourceRepository;

use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnparseableSourceFileException;
use App\Model\SourceRepositoryInterface;
use App\Services\SourceRepository\Reader\Provider;
use App\Services\YamlFileProvider\Factory as YamlFileProviderFactory;
use League\Flysystem\FilesystemException;

class Serializer
{
    private const FOO_TEMPLATE = '"%s": |' . "\n" . '%s';

    public function __construct(
        private Provider $readerProvider,
        private YamlFileProviderFactory $yamlFileProviderFactory,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws UnparseableSourceFileException
     * @throws SourceRepositoryReaderNotFoundException
     */
    public function serialize(SourceRepositoryInterface $sourceRepository): string
    {
        $reader = $this->readerProvider->find($sourceRepository);
        $listPath = rtrim(ltrim($sourceRepository->getRepositoryPath(), '/'), '/');
        $fooProvider = $this->yamlFileProviderFactory->create($reader, $listPath);

        $serializedFiles = [];

        foreach ($fooProvider->provide() as $yamlFile) {
            $content = $yamlFile->content;
            $serializedFiles[] = sprintf(
                self::FOO_TEMPLATE,
                addcslashes((string) $yamlFile->name, '"'),
                $this->createFileContentPayload($content)
            );
        }

        return implode("\n\n", $serializedFiles);
    }

    private function createFileContentPayload(string $content): string
    {
        $lines = explode("\n", trim($content));

        array_walk($lines, function (&$line) {
            $line = '  ' . $line;
        });

        return implode("\n", $lines);
    }
}
