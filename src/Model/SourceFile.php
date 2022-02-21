<?php

declare(strict_types=1);

namespace App\Model;

use App\Services\FileStoreInterface;

class SourceFile
{
    public function __construct(
        public readonly FileStoreInterface $fileStore,
        public readonly string $path,
    ) {
    }
}
