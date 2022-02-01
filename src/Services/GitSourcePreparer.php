<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
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
        private DirectoryDuplicator $directoryDuplicator,
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws UserGitRepositoryException
     * @throws DirectoryDuplicationException
     */
    public function prepare(GitSource $source, ?string $ref = null): RunSource
    {
        $target = $this->sourceFactory->createRunSource($source);

        $gitRepository = $this->userGitRepositoryPreparer->prepare($source, $ref);
        $copyableSourcePath = Path::canonicalize($gitRepository->getPath() . '/' . $source->getPath());

        $this->directoryDuplicator->duplicate($copyableSourcePath, (string) $target);

        try {
            $this->fileStoreManager->remove((string) $gitRepository);
        } catch (OutOfScopeException | RemoveException) {
        }

        return $target;
    }
}
