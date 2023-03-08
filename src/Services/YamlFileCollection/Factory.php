<?php

declare(strict_types=1);

namespace App\Services\YamlFileCollection;

use App\Services\DirectoryListingFilter;
use League\Flysystem\FilesystemReader;
use Symfony\Component\Yaml\Parser;

class Factory
{
    public function __construct(
        private Parser $yamlParser,
        private DirectoryListingFilter $listingFilter,
    ) {
    }

    /**
     * @param array<int, string> $manifestPaths
     */
    public function create(
        FilesystemReader $reader,
        string $listPath,
        array $manifestPaths = [],
    ): Provider {
        return new Provider($this->yamlParser, $this->listingFilter, $reader, $listPath, $manifestPaths);
    }
}
