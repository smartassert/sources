<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\FilePath\FilePathExceptionInterface;
use App\Exception\FileSourcePreparationException;
use App\Exception\FileStore\FileStoreExceptionInterface;
use App\Services\Source\Factory;
use App\Services\Source\Store;

class FileSourcePreparer
{
    public function __construct(
        private Factory $sourceFactory,
        private Store $sourceStore,
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws FileSourcePreparationException
     */
    public function prepare(FileSource $source): RunSource
    {
        $runSource = $this->sourceFactory->createRunSource($source);

        $exception = null;

        try {
            $this->fileStoreManager->mirror($source, $runSource);
        } catch (FileStoreExceptionInterface $exception) {
        } catch (FilePathExceptionInterface $exception) {
        }

        if ($exception instanceof \Throwable) {
            $this->sourceStore->remove($runSource);

            throw new FileSourcePreparationException($exception);
        }

        $this->sourceStore->add($runSource);

        return $runSource;
    }
}
