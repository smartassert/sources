<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\File\MutationExceptionInterface;
use App\Exception\File\PathExceptionInterface;
use App\Exception\FileSourcePreparationException;
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
        } catch (MutationExceptionInterface $exception) {
        } catch (PathExceptionInterface $exception) {
        }

        if ($exception instanceof \Throwable) {
            $this->sourceStore->remove($runSource);

            throw new FileSourcePreparationException($exception);
        }

        $this->sourceStore->add($runSource);

        return $runSource;
    }
}
