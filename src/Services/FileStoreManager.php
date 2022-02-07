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
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
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
     * @throws OutOfScopeException
     */
    public function create(string $relativePath): string
    {
        $absolutePath = $this->createAbsolutePath($relativePath);
        $this->doCreate($absolutePath);

        return (string) $absolutePath;
    }

    /**
     * @throws OutOfScopeException
     * @throws RemoveException
     */
    public function remove(string $relativePath): string
    {
        $absolutePath = $this->createAbsolutePath($relativePath);
        $this->doRemove($absolutePath);

        return (string) $absolutePath;
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
    public function add(string $fileRelativePath, string $content): void
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
    private function createAbsolutePath(string $relativePath): AbsoluteFileLocator
    {
        return $this->basePath->append($relativePath);
    }

    /**
     * @throws CreateException
     */
    private function doCreate(AbsoluteFileLocator $fileLocator): void
    {
        try {
            $this->filesystem->mkdir((string) $fileLocator);
        } catch (IOExceptionInterface $IOException) {
            throw new CreateException((string) $fileLocator, $IOException);
        }
    }

    /**
     * @throws RemoveException
     */
    private function doRemove(AbsoluteFileLocator $fileLocator): void
    {
        try {
            $this->filesystem->remove((string) $fileLocator);
        } catch (IOExceptionInterface $IOException) {
            throw new RemoveException((string) $fileLocator, $IOException);
        }
    }

    private function doExists(AbsoluteFileLocator $fileLocator): bool
    {
        return $this->filesystem->exists((string) $fileLocator);
    }
}
