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

    public function create(FilesystemReader $reader, string $listPath): Provider
    {
        return new Provider($this->yamlParser, $this->listingFilter, $reader, $listPath);
    }
}
