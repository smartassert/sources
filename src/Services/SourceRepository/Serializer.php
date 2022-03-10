<?php

declare(strict_types=1);

namespace App\Services\SourceRepository;

use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnparseableSourceFileException;
use App\Model\SourceRepositoryInterface;
use App\Services\FileLister;
use App\Services\SourceRepository\Reader\Provider;
use League\Flysystem\FilesystemException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class Serializer
{
    private const FOO_TEMPLATE = '"%s": |' . "\n" . '%s';

    public function __construct(
        private Provider $readerProvider,
        private FileLister $fileLister,
        private YamlParser $yamlParser,
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
        $files = $this->fileLister->list($reader, $listPath, ['yml', 'yaml']);

        $directoryPath = $sourceRepository->getDirectoryPath();

        $serializedFiles = [];

        foreach ($files as $file) {
            $content = $reader->read($listPath . '/' . $file);

            try {
                $this->yamlParser->parse($content);
            } catch (ParseException $parseException) {
                throw new UnparseableSourceFileException($file, $parseException);
            }

            $filePath = $this->removePathPrefix($directoryPath, $file);

            $serializedFiles[] = sprintf(
                self::FOO_TEMPLATE,
                addcslashes($filePath, '"'),
                $this->createFileContentPayload($content)
            );
        }

        return implode("\n\n", $serializedFiles);
    }

    private function removePathPrefix(string $prefix, string $path): string
    {
        $prefix = rtrim($prefix, '/') . '/';

        return str_starts_with($path, $prefix)
            ? substr($path, strlen($prefix))
            : $path;
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
