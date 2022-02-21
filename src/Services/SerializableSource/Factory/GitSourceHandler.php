<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Factory;

use App\Entity\GitSource;
use App\Entity\OriginSourceInterface;
use App\Exception\GitRepositoryException;
use App\Exception\SerializableSourceCreationException;
use App\Model\SerializableSourceInterface;
use App\Model\UserGitRepository;
use App\Services\GitRepositoryStore;
use League\Flysystem\FilesystemException;

class GitSourceHandler implements CreatorInterface, DestructorInterface
{
    public function __construct(
        private GitRepositoryStore $gitRepositoryStore,
    ) {
    }

    public function createsFor(OriginSourceInterface $origin): bool
    {
        return $origin instanceof GitSource;
    }

    /**
     * @throws SerializableSourceCreationException
     */
    public function create(OriginSourceInterface $origin, array $parameters): ?SerializableSourceInterface
    {
        if ($origin instanceof GitSource) {
            try {
                return $this->gitRepositoryStore->initialize($origin, $parameters['ref'] ?? null);
            } catch (GitRepositoryException $e) {
                throw new SerializableSourceCreationException($e);
            }
        }

        return null;
    }

    public function removes(SerializableSourceInterface $serializableSource): bool
    {
        return $serializableSource instanceof UserGitRepository;
    }

    /**
     * @throws FilesystemException
     */
    public function remove(SerializableSourceInterface $serializableSource): void
    {
        if ($serializableSource instanceof UserGitRepository) {
            $this->gitRepositoryStore->remove($serializableSource);
        }
    }
}
