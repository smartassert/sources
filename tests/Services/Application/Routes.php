<?php

declare(strict_types=1);

namespace App\Tests\Services\Application;

class Routes
{
    public function __construct(
        public readonly string $addFileUrl,
        public readonly string $removeFileUrl,
        public readonly string $createSourceUrl,
        public readonly string $listSourcesUrl,
        public readonly string $getSourceUrl,
        public readonly string $updateSourceUrl,
        public readonly string $deleteSourceUrl,
        public readonly string $prepareSourceUrl,
        public readonly string $readSourceUrl,
    ) {
    }
}
