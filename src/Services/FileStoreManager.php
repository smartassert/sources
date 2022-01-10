<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\RemoveException;
use App\Model\AbsoluteFileLocator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileStoreManager
{
    public function __construct(
        private Filesystem $filesystem
    ) {
    }

    /**
     * @throws CreateException
     * @throws RemoveException
     */
    public function initialize(AbsoluteFileLocator $fileLocator): void
    {
        $this->doInitialize($fileLocator);
    }

    /**
     * @throws RemoveException
     */
    public function remove(AbsoluteFileLocator $fileLocator): void
    {
        $this->doRemove($fileLocator);
    }

    public function exists(AbsoluteFileLocator $fileLocator): bool
    {
        return $this->doExists($fileLocator);
    }

    /**
     * @throws CreateException
     * @throws MirrorException
     * @throws NotExistsException
     * @throws RemoveException
     */
    public function mirror(AbsoluteFileLocator $source, AbsoluteFileLocator $target): void
    {
        $sourcePath = (string) $source;
        $targetPath = (string) $target;

        if (false === $this->doExists($source)) {
            throw new NotExistsException($sourcePath);
        }

        if ($sourcePath === $targetPath) {
            return;
        }

        $this->doInitialize($target);

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
