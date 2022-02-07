<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\CreateException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\ReadException;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use App\Model\AbsoluteFileLocator;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileStoreManager
{
    public function __construct(
        private AbsoluteFileLocator $basePath,
        private Filesystem $filesystem,
    ) {
    }

    /**
     * @throws CreateException
     */
    public function create(string $relativePath): void
    {
        try {
            $this->filesystem->createDirectory($relativePath);
        } catch (FilesystemException $filesystemException) {
            throw new CreateException($relativePath, $filesystemException);
        }
    }

    /**
     * @throws RemoveException
     */
    public function remove(string $relativePath): void
    {
        try {
            $this->filesystem->deleteDirectory($relativePath);
        } catch (FilesystemException $filesystemException) {
            throw new RemoveException($relativePath, $filesystemException);
        }
    }

    /**
     * @param string[] $extensions
     *
     * @throws OutOfScopeException
     *
     * @return \Traversable<string, SplFileInfo>
     */
    public function list(string $relativePath, array $extensions = []): \Traversable
    {
        $absolutePath = $this->createAbsolutePath($relativePath);

        $finder = new Finder();
        $finder->files();
        $finder->in((string) $absolutePath);
        $finder->sortByName();

        if ([] !== $extensions) {
            foreach ($extensions as $extension) {
                $finder->name('*.' . $extension);
            }
        }

        return $finder->getIterator();
    }

    /**
     * @throws WriteException
     */
    public function write(string $fileRelativePath, string $content): void
    {
        try {
            $this->filesystem->write($fileRelativePath, $content);
        } catch (FilesystemException $e) {
            throw new WriteException($fileRelativePath, $e);
        }
    }

    /**
     * @throws ReadException
     */
    public function read(string $fileRelativePath): string
    {
        try {
            return $this->filesystem->read($fileRelativePath);
        } catch (FilesystemException $e) {
            throw new ReadException($fileRelativePath, $e);
        }
    }

    /**
     * @throws OutOfScopeException
     */
    public function createAbsolutePath(string $relativePath): AbsoluteFileLocator
    {
        return $this->basePath->append($relativePath);
    }
}
