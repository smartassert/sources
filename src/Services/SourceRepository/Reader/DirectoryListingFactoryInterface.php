<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Entity\FileSource;
use App\Model\DirectoryListing;
use League\Flysystem\FilesystemException;

interface DirectoryListingFactoryInterface
{
    /**
     * @throws FilesystemException
     */
    public function list(FileSource $source): DirectoryListing;
}
