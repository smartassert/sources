<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\File\FileExceptionInterface;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Exception\SourceMirrorException;
use App\Exception\UserGitRepositoryException;
use Symfony\Component\Filesystem\Path;

class GitSourcePreparer
{
    public function __construct(
        private UserGitRepositoryPreparer $userGitRepositoryPreparer,
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws UserGitRepositoryException
     * @throws SourceMirrorException
     */
    public function prepare(RunSource $target, ?string $ref = null): void
    {
        $source = $target->getParent();

        if (!$source instanceof GitSource) {
            return;
        }

        $gitRepository = $this->userGitRepositoryPreparer->prepare($source, $ref);
        $copyableSourcePath = Path::canonicalize($gitRepository->getPath() . '/' . $source->getPath());

        try {
            $this->fileStoreManager->mirror($copyableSourcePath, (string) $target);
        } catch (FileExceptionInterface $mirrorException) {
            try {
                $this->fileStoreManager->remove((string) $target);
            } catch (OutOfScopeException | RemoveException) {
            }

            throw new SourceMirrorException($mirrorException);
        } finally {
            try {
                $this->fileStoreManager->remove((string) $gitRepository);
            } catch (OutOfScopeException | RemoveException) {
            }
        }
    }
}
