<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\File\OutOfScopeException;
use App\Exception\SourceRead\InvalidYamlException;
use App\Exception\SourceRead\ReadFileException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Model\FilePathIdentifier;
use App\Model\UserGitRepository;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class SourceSerializer
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
    public function serialize(RunSource|FileSource|UserGitRepository $source, ?string $path = null): string
    {
        $sourceDirectory = (string) $source;
        if (is_string($path)) {
            $sourceDirectory = Path::canonicalize($sourceDirectory . '/' . $path);
        }

        $sourceFiles = [];

        try {
            $sourceFiles = $this->fileStoreManager->list($sourceDirectory, ['yml', 'yaml']);
        } catch (OutOfScopeException) {
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
