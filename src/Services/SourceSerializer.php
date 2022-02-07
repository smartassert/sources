<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\ReadException;
use App\Exception\SourceRead\InvalidYamlException;
use App\Exception\SourceRead\ReadFileException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Model\FilePathIdentifier;
use League\Flysystem\FilesystemException;
use League\Flysystem\PathNormalizer;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class SourceSerializer
{
    private const DOCUMENT_TEMPLATE = '---' . "\n" . '%s' . "\n" . '...';

    public function __construct(
        private FileStoreManager $fileStoreManager,
        private YamlParser $yamlParser,
        private PathNormalizer $pathNormalizer,
    ) {
    }

    /**
     * @throws SourceReadExceptionInterface
     */
    public function serialize(string $sourceRelativePath, ?string $path = null): string
    {
        $sourceDirectory = $this->pathNormalizer->normalizePath($sourceRelativePath . '/' . $path);
        $sourceFiles = [];

        try {
            $sourceFiles = $this->fileStoreManager->list($sourceDirectory, ['yml', 'yaml']);
        } catch (FilesystemException) {
        }

        $documents = [];
        foreach ($sourceFiles as $sourceFile) {
            $content = $this->readYamlFile($sourceFile);
            $relativeSourcePath = (string) (new UnicodeString($sourceFile))->trimPrefix($sourceDirectory . '/');

            $documents[] = sprintf(
                self::DOCUMENT_TEMPLATE,
                new FilePathIdentifier($relativeSourcePath, md5($content))
            );
            $documents[] = sprintf(self::DOCUMENT_TEMPLATE, trim($content));
        }

        return implode("\n", $documents);
    }

    /**
     * @throws SourceReadExceptionInterface
     */
    private function readYamlFile(string $path): string
    {
        try {
            $content = $this->fileStoreManager->read($path);
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
}
