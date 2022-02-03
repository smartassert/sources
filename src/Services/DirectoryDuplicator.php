<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\DirectoryDuplicationException;
use App\Exception\File\FileExceptionInterface;
use App\Exception\File\MirrorException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;

class DirectoryDuplicator
{
    public function __construct(
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws DirectoryDuplicationException
     */
    public function duplicate(string $source, string $target): void
    {
        $exception = null;

        try {
            $this->fileStoreManager->mirror($source, $target);
        } catch (MirrorException $exception) {
            try {
                $this->fileStoreManager->remove($target);
            } catch (OutOfScopeException | RemoveException) {
            }
        } catch (FileExceptionInterface $exception) {
        }

        if ($exception instanceof \Throwable) {
            throw new DirectoryDuplicationException($exception);
        }
    }
}
