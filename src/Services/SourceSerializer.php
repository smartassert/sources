<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\SourceRead\InvalidYamlException;
use App\Exception\SourceRead\ReadFileException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Exception\Storage\ReadException;
use App\Model\FilePathIdentifier;
use App\Model\SourceFileCollection;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class SourceSerializer
{
    private const DOCUMENT_TEMPLATE = '---' . "\n" . '%s' . "\n" . '...';

    public function __construct(
        private YamlParser $yamlParser,
    ) {
    }

    /**
     * @throws SourceReadExceptionInterface
     */
    public function serialize(FileStoreInterface $store, SourceFileCollection $files): string
    {
        $documents = [];

        foreach ($files as $file) {
            $content = $this->readYamlFile($store, $file);

            $documents[] = sprintf(
                self::DOCUMENT_TEMPLATE,
                new FilePathIdentifier($this->removePathPrefix($files->pathPrefix, $file), md5($content))
            );
            $documents[] = sprintf(self::DOCUMENT_TEMPLATE, trim($content));
        }

        return implode("\n", $documents);
    }

    /**
     * @throws SourceReadExceptionInterface
     */
    private function readYamlFile(FileStoreInterface $sourceFileStore, string $path): string
    {
        try {
            $content = $sourceFileStore->read($path);
        } catch (ReadException) {
            throw new ReadFileException($path);
        }

        try {
            $this->yamlParser->parse($content);
        } catch (ParseException $parseException) {
            throw new InvalidYamlException($path, $parseException);
        }

        return $content;
    }

    private function removePathPrefix(string $prefix, string $path): string
    {
        return str_starts_with($path, $prefix)
            ? substr($path, strlen($prefix))
            : $path;
    }
}
