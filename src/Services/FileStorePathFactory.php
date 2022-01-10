<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\File\OutOfScopeException;
use App\Model\AbsoluteFileLocator;
use App\Model\FileLocatorInterface;

class FileStorePathFactory
{
    public function __construct(
        private AbsoluteFileLocator $basePath
    ) {
    }

    /**
     * @throws OutOfScopeException
     */
    public function create(FileLocatorInterface $fileLocator): AbsoluteFileLocator
    {
        $path = clone $this->basePath;
        $path->append((string) $fileLocator);

        return $path;
    }
}
