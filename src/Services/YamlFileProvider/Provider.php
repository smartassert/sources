<?php

declare(strict_types=1);

namespace App\Services\YamlFileProvider;

use App\Exception\UnparseableSourceFileException;
use App\Services\DirectoryListingFilter;
use League\Flysystem\FilesystemReader;
use SmartAssert\YamlFile\Model\YamlFile;
use SmartAssert\YamlFile\Provider\ProviderInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class Provider implements ProviderInterface
{
    private string $path;

    public function __construct(
        private Parser $yamlParser,
        private DirectoryListingFilter $listingFilter,
        private FilesystemReader $reader,
        string $path,
    ) {
        $this->path = rtrim(ltrim($path, '/'), '/');
    }

    public function provide(): \Generator
    {
        $sourceRepositoryDirectoryListing = $this->reader->listContents($this->path, true);
        $files = $this->listingFilter->filter($sourceRepositoryDirectoryListing, $this->path, ['yaml', 'yml']);

        foreach ($files as $file) {
            $content = $this->reader->read($this->path . '/' . $file);

            try {
                $this->yamlParser->parse($content);
            } catch (ParseException $parseException) {
                throw new UnparseableSourceFileException($file, $parseException);
            }

            $filePath = $this->removePathPrefix($this->path, $file);

            yield YamlFile::create($filePath, $content);
        }
    }

    private function removePathPrefix(string $prefix, string $path): string
    {
        $prefix = rtrim($prefix, '/') . '/';

        return str_starts_with($path, $prefix)
            ? substr($path, strlen($prefix))
            : $path;
    }
}
