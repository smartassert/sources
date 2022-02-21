<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\SourceFileCollection;
use League\Flysystem\FilesystemException;
use League\Flysystem\PathNormalizer;

class SerializableSourceLister
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
    ) {
    }

    public function list(FileStoreInterface $sourceFileStore, string $path): SourceFileCollection
    {
        $path = $this->pathNormalizer->normalizePath($path);

        $sourceFiles = [];

        try {
            $sourceFilePaths = $sourceFileStore->list($path, ['yml', 'yaml']);

            foreach ($sourceFilePaths as $sourceFilePath) {
                $sourceFiles[] = $this->pathNormalizer->normalizePath($path . '/' . $sourceFilePath);
            }
        } catch (FilesystemException) {
        }

        return new SourceFileCollection($sourceFiles, $path . '/');
    }
}
