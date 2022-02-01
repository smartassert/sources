<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Exception\UserGitRepositoryException;
use Symfony\Component\Filesystem\Path;

class RunSourcePreparer
{
    public function __construct(
        private DirectoryDuplicator $directoryDuplicator,
        private UserGitRepositoryPreparer $gitRepositoryPreparer,
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws DirectoryDuplicationException
     * @throws UserGitRepositoryException
     */
    public function prepare(RunSource $target): void
    {
        $source = $target->getParent();
        if ($source instanceof FileSource) {
            $this->directoryDuplicator->duplicate((string) $source, (string) $target);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryPreparer->prepare($source, $target->getParameters()['ref'] ?? null);
            $copyableSourcePath = Path::canonicalize($gitRepository->getPath() . '/' . $source->getPath());

            $this->directoryDuplicator->duplicate($copyableSourcePath, (string) $target);

            try {
                $this->fileStoreManager->remove((string) $gitRepository);
            } catch (OutOfScopeException | RemoveException) {
            }
        }
    }
}
