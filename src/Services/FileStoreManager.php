<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Model\AbsoluteFileLocator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileStoreManager
{
    public function __construct(
        private AbsoluteFileLocator $basePath,
        private Filesystem $filesystem
    ) {
    }

    /**
     * @throws CreateException
     * @throws OutOfScopeException
     */
    public function create(string $relativePath): AbsoluteFileLocator
    {
        return $this->doCreate($this->createAbsolutePath($relativePath));
    }

    /**
     * @throws OutOfScopeException
     * @throws RemoveException
     */
    public function remove(string $relativePath): AbsoluteFileLocator
    {
        return $this->doRemove($this->createAbsolutePath($relativePath));
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
     * @return AbsoluteFileLocator The absolute target path
     */
    public function mirror(string $sourceRelativePath, string $targetRelativePath): AbsoluteFileLocator
    {
        try {
            $sourceAbsoluteLocator = $this->createAbsolutePath($sourceRelativePath);
        } catch (OutOfScopeException $sourceOutOfScopeException) {
            throw $sourceOutOfScopeException->withContext('source');
        }

        try {
            $targetAbsoluteLocator = $this->createAbsolutePath($targetRelativePath);
        } catch (OutOfScopeException $targetOutOfScopeException) {
            throw $targetOutOfScopeException->withContext('target');
        }

        $sourcePath = (string) $sourceAbsoluteLocator;
        $targetPath = (string) $targetAbsoluteLocator;

        if (false === $this->doExists($sourceAbsoluteLocator)) {
            throw new NotExistsException($sourcePath);
        }

        if ($sourcePath === $targetPath) {
            return $targetAbsoluteLocator;
        }

        $this->doRemove($targetAbsoluteLocator);
        $this->doCreate($targetAbsoluteLocator);

        try {
            $this->filesystem->mirror($sourcePath, $targetPath);
        } catch (IOExceptionInterface $IOException) {
            throw new MirrorException($sourcePath, $targetPath, $IOException);
        }

        return $targetAbsoluteLocator;
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
    private function doCreate(AbsoluteFileLocator $fileLocator): AbsoluteFileLocator
    {
        try {
            $this->filesystem->mkdir((string) $fileLocator);
        } catch (IOExceptionInterface $IOException) {
            throw new CreateException((string) $fileLocator, $IOException);
        }

        return $fileLocator;
    }

    /**
     * @throws RemoveException
     */
    private function doRemove(AbsoluteFileLocator $fileLocator): AbsoluteFileLocator
    {
        try {
            $this->filesystem->remove((string) $fileLocator);
        } catch (IOExceptionInterface $IOException) {
            throw new RemoveException((string) $fileLocator, $IOException);
        }

        return $fileLocator;
    }

    private function doExists(AbsoluteFileLocator $fileLocator): bool
    {
        return $this->filesystem->exists((string) $fileLocator);
    }
}
