<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\FileLocatorInterface;
use App\Model\FileStore;

class FileStoreFactory
{
    public function __construct(
        private string $basePath
    ) {
    }

    public function create(FileLocatorInterface $fileLocator): FileStore
    {
        return new FileStore($this->basePath, $fileLocator);
    }
}
