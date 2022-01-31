<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Exception\File\FileExceptionInterface;
use App\Exception\File\MirrorException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Exception\SourceMirrorException;

class FileSourcePreparer
{
    public function __construct(
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws SourceMirrorException
     */
    public function prepare(RunSource $target): void
    {
        $source = $target->getParent();
        if (!$source instanceof FileSource) {
            return;
        }

        $exception = null;

        try {
            $this->fileStoreManager->mirror((string)$source, (string)$target);
        } catch (MirrorException $mirrorException) {
            $exception = $mirrorException;

            try {
                $this->fileStoreManager->remove((string) $target);
            } catch (OutOfScopeException | RemoveException) {
            }
        } catch (FileExceptionInterface $fileException) {
            $exception = $fileException;
        }

        if ($exception instanceof \Throwable) {
            throw new SourceMirrorException($exception);
        }
    }
}
