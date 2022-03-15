<?php

declare(strict_types=1);

namespace App\Services\YamlFileProvider;

use App\Exception\UnparseableSourceFileException;
use App\Services\DirectoryListingFilter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use SmartAssert\YamlFile\Collection\ProviderInterface;
use SmartAssert\YamlFile\Exception\ProvisionException;
use SmartAssert\YamlFile\YamlFile;
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

    public function getYamlFiles(): \Generator
    {
        try {
            $sourceRepositoryDirectoryListing = $this->reader->listContents($this->path, true);
        } catch (FilesystemException $e) {
            throw new ProvisionException(sprintf('Listing contents failed for "%s"', $this->path), 0, $e);
        }

        $files = $this->listingFilter->filter($sourceRepositoryDirectoryListing, $this->path, ['yaml', 'yml']);

        foreach ($files as $file) {
            $readPath = $this->path . '/' . $file;

            try {
                $content = rtrim($this->reader->read($readPath));
            } catch (FilesystemException $e) {
                throw new ProvisionException(sprintf('File read failed for "%s"', $readPath), 0, $e);
            }

            try {
                $this->yamlParser->parse($content);
            } catch (ParseException $parseException) {
                throw new ProvisionException(
                    sprintf('Unable to parse content for "%s"', $readPath),
                    0,
                    new UnparseableSourceFileException($file, $parseException)
                );
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
