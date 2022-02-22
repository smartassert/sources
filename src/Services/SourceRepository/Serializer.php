<?php

declare(strict_types=1);

namespace App\Services\SourceRepository;

use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Exception\UnparseableSourceFileException;
use App\Model\FilePathIdentifier;
use App\Model\SourceRepositoryInterface;
use App\Services\FileLister;
use App\Services\SourceRepository\Reader\Provider;
use League\Flysystem\FilesystemException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class Serializer
{
    private const DOCUMENT_TEMPLATE = '---' . "\n" . '%s' . "\n" . '...';

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

        $documents = [];
        foreach ($files as $file) {
            $content = $reader->read($listPath . '/' . $file);

            try {
                $this->yamlParser->parse($content);
            } catch (ParseException $parseException) {
                throw new UnparseableSourceFileException($file, $parseException);
            }

            $documents[] = sprintf(
                self::DOCUMENT_TEMPLATE,
                new FilePathIdentifier(
                    $this->removePathPrefix($sourceRepository->getDirectoryPath(), $file),
                    md5($content)
                )
            );
            $documents[] = sprintf(self::DOCUMENT_TEMPLATE, trim($content));
        }

        return implode("\n", $documents);
    }

    private function removePathPrefix(string $prefix, string $path): string
    {
        $prefix = rtrim($prefix, '/') . '/';

        return str_starts_with($path, $prefix)
            ? substr($path, strlen($prefix))
            : $path;
    }
}
