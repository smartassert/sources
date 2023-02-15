<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Exception\EmptyEntityIdException;
use App\Model\UserGitRepository;

class UserGitRepositoryFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     */
    public function create(GitSource $source): UserGitRepository
    {
        return new UserGitRepository($this->entityIdFactory->create(), $source);
    }
}
