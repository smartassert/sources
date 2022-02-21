<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\FilePathIdentifier;
use App\Model\SourceFileCollection;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
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
     * @throws ParseException
     * @throws FilesystemException
     */
    public function serialize(FilesystemReader $reader, SourceFileCollection $files): string
    {
        $documents = [];

        foreach ($files as $file) {
            $content = $this->readYamlFile($reader, $file);

            $documents[] = sprintf(
                self::DOCUMENT_TEMPLATE,
                new FilePathIdentifier($this->removePathPrefix($files->pathPrefix, $file), md5($content))
            );
            $documents[] = sprintf(self::DOCUMENT_TEMPLATE, trim($content));
        }

        return implode("\n", $documents);
    }

    /**
     * @throws ParseException
     * @throws FilesystemException
     */
    private function readYamlFile(FilesystemReader $reader, string $path): string
    {
        $content = $reader->read($path);
        $this->yamlParser->parse($content);

        return $content;
    }

    private function removePathPrefix(string $prefix, string $path): string
    {
        return str_starts_with($path, $prefix)
            ? substr($path, strlen($prefix))
            : $path;
    }
}
