<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Services\Source\Factory;

class FileSourcePreparer
{
    public function __construct(
        private Factory $sourceFactory,
        private DirectoryDuplicator $directoryDuplicator,
    ) {
    }

    /**
     * @throws DirectoryDuplicationException
     */
    public function prepare(FileSource $source): RunSource
    {
        $target = $this->sourceFactory->createRunSource($source);

        $this->directoryDuplicator->duplicate((string) $source, (string) $target);

        return $target;
    }
}
