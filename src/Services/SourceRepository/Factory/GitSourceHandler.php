<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\GitSource;
use App\Entity\SourceOriginInterface;
use App\Exception\GitRepositoryException;
use App\Exception\SourceRepositoryCreationException;
use App\Model\SourceRepositoryInterface;
use App\Model\UserGitRepository;
use App\Services\GitRepositoryStore;
use League\Flysystem\FilesystemException;

class GitSourceHandler implements CreatorInterface, DestructorInterface
{
    public function __construct(
        private GitRepositoryStore $gitRepositoryStore,
    ) {
    }

    public function createsFor(SourceOriginInterface $source): bool
    {
        return $source instanceof GitSource;
    }

    /**
     * @throws SourceRepositoryCreationException
     */
    public function create(SourceOriginInterface $source, array $parameters): ?SourceRepositoryInterface
    {
        if ($source instanceof GitSource) {
            try {
                return $this->gitRepositoryStore->initialize($source, $parameters['ref'] ?? null);
            } catch (GitRepositoryException $e) {
                throw new SourceRepositoryCreationException($e);
            }
        }

        return null;
    }

    public function removes(SourceRepositoryInterface $sourceRepository): bool
    {
        return $sourceRepository instanceof UserGitRepository;
    }

    /**
     * @throws FilesystemException
     */
    public function remove(SourceRepositoryInterface $sourceRepository): void
    {
        if ($sourceRepository instanceof UserGitRepository) {
            $this->gitRepositoryStore->remove($sourceRepository);
        }
    }
}
