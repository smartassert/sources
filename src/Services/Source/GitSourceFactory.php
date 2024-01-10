<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Exception\EmptyEntityIdException;
use App\Exception\ErrorResponseException;
use App\Repository\GitSourceRepository;
use App\Request\GitSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\UsersSecurityBundle\Security\User;

readonly class GitSourceFactory
{
    public function __construct(
        private EntityIdFactory $entityIdFactory,
        private GitSourceRepository $gitSourceRepository,
        private Mutator $sourceMutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws ErrorResponseException
     */
    public function create(User $user, GitSourceRequest $request): GitSource
    {
        $source = $this->gitSourceRepository->findOneBy([
            'userId' => $user->getUserIdentifier(),
            'label' => $request->label,
            'deletedAt' => null,
            'hostUrl' => $request->hostUrl,
            'path' => $request->path,
        ]);

        if ($source instanceof GitSource) {
            return $source;
        }

        return $this->sourceMutator->updateGit(
            new GitSource($this->entityIdFactory->create(), $user->getUserIdentifier()),
            $request
        );
    }
}
