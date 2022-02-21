<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\SourceFileCollection;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\PathNormalizer;

class SerializableSourceLister
{
    public function __construct(
        private FileLister $fileLister,
        private PathNormalizer $pathNormalizer,
    ) {
    }

    public function list(FilesystemReader $reader, string $path): SourceFileCollection
    {
        $path = $this->pathNormalizer->normalizePath($path);

        $sourceFiles = [];

        try {
            $sourceFilePaths = $this->fileLister->list($reader, $path, ['yml', 'yaml']);

            foreach ($sourceFilePaths as $sourceFilePath) {
                $sourceFiles[] = $this->pathNormalizer->normalizePath($path . '/' . $sourceFilePath);
            }
        } catch (FilesystemException) {
        }

        return new SourceFileCollection($sourceFiles, $path . '/');
    }
}
