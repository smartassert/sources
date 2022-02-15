<?php

declare(strict_types=1);

namespace App\Tests\Services;

use League\Flysystem\FilesystemOperator;

class FileStoreFixtureCreator
{
    public function __construct(
        private FilesystemOperator $defaultStorage,
        private DirectoryLister $directoryLister,
        private string $fixturesBasePath,
    ) {
    }

    public function copySetTo(string $originRelativePath, string $targetRelativeDirectory): void
    {
        $originDirectoryIterator = new \RecursiveDirectoryIterator($this->fixturesBasePath . $originRelativePath);
        $originFiles = $this->directoryLister->list($originDirectoryIterator);

        foreach ($originFiles as $relativePath => $file) {
            $this->copyFilesystemFileToFileStore(
                $file->getPathname(),
                $targetRelativeDirectory . '/' . $relativePath
            );
        }
    }

    public function copyTo(string $originRelativePath, string $targetRelativePath): void
    {
        $this->copyFilesystemFileToFileStore(
            $this->getFixturePath($originRelativePath),
            $targetRelativePath
        );
    }

    /**
     * @return string[]
     */
    public function listFixtureSetFiles(string $relativePath): array
    {
        $absolutePath = $this->getFixturePath($relativePath);
        $paths = array_keys($this->directoryLister->list(new \RecursiveDirectoryIterator($absolutePath)));
        sort($paths);

        return $paths;
    }

    public function getFixturePath(string $relativePath): string
    {
        return $this->fixturesBasePath . $relativePath;
    }

    private function copyFilesystemFileToFileStore(string $sourceAbsolutePath, string $targetRelativePath): void
    {
        $this->defaultStorage->write($targetRelativePath, (string) file_get_contents($sourceAbsolutePath));
    }
}
