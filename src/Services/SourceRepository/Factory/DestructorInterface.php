<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Model\SourceRepositoryInterface;
use League\Flysystem\FilesystemException;

interface DestructorInterface
{
    public function removes(SourceRepositoryInterface $sourceRepository): bool;

    /**
     * @throws FilesystemException
     */
    public function remove(SourceRepositoryInterface $sourceRepository): void;
}
