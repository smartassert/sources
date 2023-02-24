<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Exception\EmptyEntityIdException;
use App\Exception\NonUniqueEntityLabelException;
use App\Repository\GitSourceRepository;
use App\Request\GitSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\UsersSecurityBundle\Security\User;

class GitSourceFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
        private readonly GitSourceRepository $gitSourceRepository,
        private readonly Mutator $sourceMutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws NonUniqueEntityLabelException
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
