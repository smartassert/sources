<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;

class FileSourcePreparer
{
    public function __construct(private DirectoryDuplicator $directoryDuplicator)
    {
    }

    /**
     * @throws DirectoryDuplicationException
     */
    public function prepare(FileSource $source): RunSource
    {
        $target = new RunSource($source);

        $this->directoryDuplicator->duplicate((string) $source, (string) $target);

        return $target;
    }
}
