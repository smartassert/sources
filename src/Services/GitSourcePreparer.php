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
use App\Services\Source\Factory;
use Symfony\Component\Filesystem\Path;

class GitSourcePreparer
{
    public function __construct(
        private Factory $sourceFactory,
        private UserGitRepositoryPreparer $userGitRepositoryPreparer,
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws UserGitRepositoryException
     * @throws SourceMirrorException
     */
    public function prepare(GitSource $source, ?string $ref = null): RunSource
    {
        $gitRepository = $this->userGitRepositoryPreparer->prepare($source, $ref);

        $runSourceParameters = [];
        if (is_string($ref)) {
            $runSourceParameters['ref'] = $ref;
        }

        $runSource = $this->sourceFactory->createRunSource($source, $runSourceParameters);
        $copyableSourcePath = Path::canonicalize($gitRepository->getPath() . '/' . $source->getPath());

        try {
            $this->fileStoreManager->mirror($copyableSourcePath, (string) $runSource);
        } catch (FileExceptionInterface $mirrorException) {
            throw new SourceMirrorException($mirrorException);
        } finally {
            try {
                $this->fileStoreManager->remove((string) $gitRepository);
            } catch (OutOfScopeException | RemoveException) {
            }
        }

        return $runSource;
    }
}
