<?php

declare(strict_types=1);

namespace App\Services\SerializableSource;

use App\Exception\SerializableSourceReaderNotFoundException;
use App\Exception\UnparseableSourceFileException;
use App\Model\FilePathIdentifier;
use App\Model\SerializableSourceInterface;
use App\Services\FileLister;
use App\Services\SerializableSource\Reader\Provider;
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
     * @throws SerializableSourceReaderNotFoundException
     */
    public function serialize(SerializableSourceInterface $serializableSource): string
    {
        $reader = $this->readerProvider->find($serializableSource);

        $sourcePath = $serializableSource->getFilePath();
        $listPath = rtrim($sourcePath . '/' . ltrim($serializableSource->getSerializableSourcePath(), '/'), '/');
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
                new FilePathIdentifier($this->removePathPrefix($sourcePath, $file), md5($content))
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
