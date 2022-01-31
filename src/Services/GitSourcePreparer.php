<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
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
     * @throws CreateException
     * @throws OutOfScopeException
     * @throws RemoveException
     * @throws MirrorException
     * @throws NotExistsException
     * @throws UserGitRepositoryException
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
        } catch (CreateException | NotExistsException | RemoveException $exception) {
            $this->fileStoreManager->remove((string) $gitRepository);

            if (null == $exception->getContext()) {
                $exception = $exception->withContext('target');
            }

            throw $exception;
        } catch (MirrorException $mirrorException) {
            $this->fileStoreManager->remove((string) $gitRepository);

            throw $mirrorException;
        }

        $this->fileStoreManager->remove((string) $gitRepository);

        return $runSource;
    }
}
