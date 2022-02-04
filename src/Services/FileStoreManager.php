<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
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
     * @throws CreateException
     * @throws MirrorException
     * @throws NotExistsException
     * @throws OutOfScopeException
     * @throws RemoveException
     *
     * @return string The absolute target path
     */
    public function mirror(string $sourceRelativePath, string $targetRelativePath): string
    {
        try {
            $sourceAbsolutePath = $this->createAbsolutePath($sourceRelativePath);
        } catch (OutOfScopeException $sourceOutOfScopeException) {
            throw $sourceOutOfScopeException->withContext('source');
        }

        try {
            $targetAbsolutePath = $this->createAbsolutePath($targetRelativePath);
        } catch (OutOfScopeException $targetOutOfScopeException) {
            throw $targetOutOfScopeException->withContext('target');
        }

        $sourcePath = (string) $sourceAbsolutePath;
        $targetPath = (string) $targetAbsolutePath;

        if (false === $this->doExists($sourceAbsolutePath)) {
            throw (new NotExistsException($sourcePath))->withContext('source');
        }

        if ($sourcePath === $targetPath) {
            return $targetPath;
        }

        try {
            $this->doRemove($targetAbsolutePath);
            $this->doCreate($targetAbsolutePath);
        } catch (RemoveException | CreateException $exception) {
            throw $exception->withContext('target');
        }

        try {
            $this->filesystem->mirror($sourcePath, $targetPath);
        } catch (IOExceptionInterface $IOException) {
            throw new MirrorException($sourcePath, $targetPath, $IOException);
        }

        return $targetPath;
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
