<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\RunSource;
use App\Exception\File\OutOfScopeException;
use App\Exception\SourceRead\InvalidYamlException;
use App\Exception\SourceRead\ReadFileException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Model\FilePathIdentifier;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class RunSourceBuilder
{
    private const DOCUMENT_TEMPLATE = '---' . "\n" . '%s' . "\n" . '...';

    public function __construct(
        private FileStoreManager $fileStoreManager,
        private YamlParser $yamlParser,
    ) {
    }

    /**
     * @throws SourceReadExceptionInterface
     */
    public function build(RunSource $source): string
    {
        $sourceFiles = [];

        try {
            $sourceFiles = $this->fileStoreManager->list((string) $source, ['yml', 'yaml']);
        } catch (OutOfScopeException) {
            // RunSource::getPath() is guaranteed to be relative and not out of scope with the file store base path
        }

        $documents = [];
        foreach ($sourceFiles as $sourceFile) {
            $content = $this->readYamlFile($sourceFile);

            $documents[] = sprintf(self::DOCUMENT_TEMPLATE, new FilePathIdentifier($sourceFile, md5($content)));
            $documents[] = sprintf(self::DOCUMENT_TEMPLATE, trim($content));
        }

        return implode("\n", $documents);
    }

    /**
     * @throws SourceReadExceptionInterface
     */
    private function readYamlFile(SplFileInfo $file): string
    {
        $content = file_get_contents($file->getPathname());
        if (false === $content) {
            throw new ReadFileException($file);
        }

        try {
            $this->yamlParser->parse($content);
        } catch (ParseException $parseException) {
            throw new InvalidYamlException($file, $parseException);
        }

        return $content;
    }
}
