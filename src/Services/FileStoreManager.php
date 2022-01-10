<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Model\AbsoluteFileLocator;
use App\Model\FileLocatorInterface;
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
     * @throws OutOfScopeException
     */
    public function createPath(FileLocatorInterface $fileLocator): AbsoluteFileLocator
    {
        $path = clone $this->basePath;
        $path->append((string) $fileLocator);

        return $path;
    }

    /**
     * @throws CreateException
     * @throws OutOfScopeException
     * @throws RemoveException
     */
    public function initialize(FileLocatorInterface $fileLocator): void
    {
        $this->doInitialize(
            $this->createPath($fileLocator)
        );
    }

    /**
     * @throws OutOfScopeException
     * @throws RemoveException
     */
    public function remove(FileLocatorInterface $fileLocator): void
    {
        $this->doRemove(
            $this->createPath($fileLocator)
        );
    }

    /**
     * @throws OutOfScopeException
     */
    public function exists(FileLocatorInterface $fileLocator): bool
    {
        return $this->doExists(
            $this->createPath($fileLocator)
        );
    }

    /**
     * @throws CreateException
     * @throws MirrorException
     * @throws NotExistsException
     * @throws OutOfScopeException
     * @throws RemoveException
     */
    public function mirror(FileLocatorInterface $source, FileLocatorInterface $target): void
    {
        $sourceAbsoluteLocator = $this->createPath($source);
        $targetAbsoluteLocator = $this->createPath($target);

        $sourcePath = (string) $sourceAbsoluteLocator;
        $targetPath = (string) $targetAbsoluteLocator;

        if (false === $this->doExists($sourceAbsoluteLocator)) {
            throw new NotExistsException($sourcePath);
        }

        if ($sourcePath === $targetPath) {
            return;
        }

        $this->doInitialize($targetAbsoluteLocator);

        try {
            $this->filesystem->mirror($sourcePath, $targetPath);
        } catch (IOExceptionInterface $IOException) {
            throw new MirrorException($sourcePath, $targetPath, $IOException);
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

    /**
     * @throws CreateException
     * @throws RemoveException
     */
    private function doInitialize(AbsoluteFileLocator $fileLocator): void
    {
        $this->doRemove($fileLocator);

        try {
            $this->filesystem->mkdir((string) $fileLocator);
        } catch (IOExceptionInterface $IOException) {
            throw new CreateException((string) $fileLocator, $IOException);
        }
    }

    private function doExists(AbsoluteFileLocator $fileLocator): bool
    {
        return $this->filesystem->exists((string) $fileLocator);
    }
}
