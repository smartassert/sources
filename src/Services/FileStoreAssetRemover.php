<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\FileStoreAssetRemoverException;
use App\Model\FileLocatorInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class FileStoreAssetRemover
{
    public function __construct(
        private FileStoreFactory $fileStoreFactory,
        private Filesystem $filesystem,
    ) {
    }

    /**
     * @throws FileStoreAssetRemoverException
     */
    public function remove(FileLocatorInterface $fileLocator): bool
    {
        $fileStore = $this->fileStoreFactory->create($fileLocator);

        $path = Path::canonicalize((string) $fileStore);

        if (false === str_starts_with($path, '/')) {
            throw FileStoreAssetRemoverException::createPathNotAbsoluteException($fileStore);
        }

        if (strlen($path) <= strlen($fileStore->getBasePath())) {
            throw FileStoreAssetRemoverException::createPathIsOutsideBasePathException($fileStore);
        }

        if (false === $this->filesystem->exists($path)) {
            return true;
        }

        try {
            $this->filesystem->remove($path);
        } catch (IOExceptionInterface $IOException) {
            throw FileStoreAssetRemoverException::createFilesystemErrorException($fileStore, $IOException);
        }

        return true;
    }
}
