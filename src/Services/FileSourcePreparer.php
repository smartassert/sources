<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicator\DirectoryDuplicatorExceptionInterface;
use App\Exception\FileSourcePreparationException;
use App\Services\Source\Factory;
use App\Services\Source\Store;

class FileSourcePreparer
{
    public function __construct(
        private Factory $sourceFactory,
        private DirectoryDuplicator $directoryDuplicator,
        private FileStoreFactory $fileStoreFactory,
        private Store $sourceStore
    ) {
    }

    /**
     * @throws FileSourcePreparationException
     */
    public function prepare(FileSource $source): RunSource
    {
        $runSource = $this->sourceFactory->createRunSource($source);

        $sourceFileStore = $this->fileStoreFactory->create($source);
        $targetFileStore = $this->fileStoreFactory->create($runSource);

        try {
            $this->directoryDuplicator->duplicate($sourceFileStore, $targetFileStore);
        } catch (DirectoryDuplicatorExceptionInterface $e) {
            $this->sourceStore->remove($runSource);

            throw new FileSourcePreparationException($e);
        }

        $this->sourceStore->add($runSource);

        return $runSource;
    }
}
