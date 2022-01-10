<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\FilePath\CreateException;
use App\Exception\FileStore\MirrorException;
use App\Exception\FileStore\NonAbsolutePathException;
use App\Exception\FileStore\NotExistsException;
use App\Exception\FileStore\OutOfScopeException;
use App\Exception\FileStore\RemoveException;
use App\Model\FileLocatorInterface;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class FileStoreManager
{
    public function __construct(
        private string $basePath,
        private Filesystem $filesystem
    ) {
    }

    /**
     * @throws NonAbsolutePathException
     * @throws OutOfScopeException
     */
    public function createPath(FileLocatorInterface $fileLocator): string
    {
        try {
            $path = Path::makeAbsolute((string) $fileLocator, $this->basePath);
        } catch (InvalidArgumentException $exception) {
            throw new NonAbsolutePathException($this->basePath, $exception);
        }

        if (false === Path::isBasePath($this->basePath, $path)) {
            throw new OutOfScopeException($path, $this->basePath);
        }

        return $path;
    }

    /**
     * @throws CreateException
     * @throws NonAbsolutePathException
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
     * @throws NonAbsolutePathException
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
     * @throws NonAbsolutePathException
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
     * @throws NonAbsolutePathException
     * @throws NotExistsException
     * @throws OutOfScopeException
     * @throws RemoveException
     */
    public function mirror(FileLocatorInterface $source, FileLocatorInterface $target): void
    {
        $sourcePath = $this->createPath($source);
        $targetPath = $this->createPath($target);

        if (false === $this->doExists($sourcePath)) {
            throw new NotExistsException($sourcePath);
        }

        if ($sourcePath === $targetPath) {
            return;
        }

        $this->doInitialize($targetPath);

        try {
            $this->filesystem->mirror($sourcePath, $targetPath);
        } catch (IOExceptionInterface $IOException) {
            throw new MirrorException($sourcePath, $targetPath, $IOException);
        }
    }

    /**
     * @throws RemoveException
     */
    private function doRemove(string $path): void
    {
        try {
            $this->filesystem->remove($path);
        } catch (IOExceptionInterface $IOException) {
            throw new RemoveException($path, $IOException);
        }
    }

    /**
     * @throws CreateException
     * @throws RemoveException
     */
    private function doInitialize(string $path): void
    {
        $this->doRemove($path);

        try {
            $this->filesystem->mkdir($path);
        } catch (IOExceptionInterface $IOException) {
            throw new CreateException($path, $IOException);
        }
    }

    private function doExists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }
}
