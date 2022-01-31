<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\File\FileExceptionInterface;
use App\Exception\SourceMirrorException;
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
     * @throws SourceMirrorException
     */
    public function prepare(FileSource $source): RunSource
    {
        $runSource = $this->sourceFactory->createRunSource($source);

        try {
            $this->fileStoreManager->mirror((string) $source, (string) $runSource);
        } catch (FileExceptionInterface $exception) {
            $this->sourceStore->remove($runSource);

            throw new SourceMirrorException($exception);
        }

        $this->sourceStore->add($runSource);

        return $runSource;
    }
}
