<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Reader;

use App\Entity\FileSourceInterface;
use App\Model\DirectoryListing;
use League\Flysystem\FilesystemException;

interface DirectoryListingFactoryInterface
{
    /**
     * @throws FilesystemException
     */
    public function list(FileSourceInterface $source): DirectoryListing;
}
