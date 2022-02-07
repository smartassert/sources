<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\CreateException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\ReadException;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use App\Model\AbsoluteFileLocator;
use League\Flysystem\Filesystem as FlyFilesystem;
use League\Flysystem\FilesystemException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileStoreManager
{
    public function __construct(
        private AbsoluteFileLocator $basePath,
        private Filesystem $filesystem,
        private FlyFilesystem $flyFilesystem,
    ) {
    }

    /**
     * @throws CreateException
     */
    public function create(string $relativePath): void
    {
        try {
            $this->flyFilesystem->createDirectory($relativePath);
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
            $this->flyFilesystem->deleteDirectory($relativePath);
        } catch (FilesystemException $filesystemException) {
            throw new RemoveException($relativePath, $filesystemException);
        }
    }

    /**
     * @throws OutOfScopeException
     */
    public function exists(string $relativePath): bool
    {
        return $this->doExists($this->createAbsolutePath($relativePath));
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
        $fileRelativePath = Path::canonicalize($fileRelativePath);

        try {
            $this->flyFilesystem->write($fileRelativePath, $content);
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
            return $this->flyFilesystem->read($fileRelativePath);
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

    private function doExists(AbsoluteFileLocator $fileLocator): bool
    {
        return $this->filesystem->exists((string) $fileLocator);
    }
}
