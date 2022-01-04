<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\DirectoryDuplicator\DuplicationException;
use App\Exception\DirectoryDuplicator\MissingSourceException;
use App\Exception\DirectoryDuplicator\TargetCreationException;
use App\Exception\DirectoryDuplicator\TargetRemovalException;
use App\Model\FileLocatorInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class DirectoryDuplicator
{
    public function __construct(
        private Filesystem $filesystem
    ) {
    }

    /**
     * @throws DuplicationException
     * @throws MissingSourceException
     * @throws TargetCreationException
     * @throws TargetRemovalException
     */
    public function duplicate(FileLocatorInterface $source, FileLocatorInterface $target): void
    {
        $sourcePath = Path::canonicalize((string) $source);
        $targetPath = Path::canonicalize((string) $target);

        if ($sourcePath === $targetPath) {
            return;
        }

        if (false === $this->filesystem->exists($sourcePath)) {
            throw new MissingSourceException($source);
        }

        try {
            $this->filesystem->remove($targetPath);
        } catch (IOExceptionInterface $IOException) {
            throw new TargetRemovalException($target, $IOException);
        }

        try {
            $this->filesystem->mkdir($targetPath);
        } catch (IOExceptionInterface $IOException) {
            throw new TargetCreationException($target, $IOException);
        }

        try {
            $this->filesystem->mirror($sourcePath, $targetPath);
        } catch (IOExceptionInterface $IOException) {
            throw new DuplicationException($source, $target, $IOException);
        }
    }
}
