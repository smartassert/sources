<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Exception\EmptyEntityIdException;
use App\Repository\GitSourceRepository;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\UsersSecurityBundle\Security\User;

class GitSourceFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
        private readonly GitSourceRepository $gitSourceRepository,
        private readonly SourceRepository $sourceRepository,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
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

        if (null === $source) {
            $source = new GitSource(
                $this->entityIdFactory->create(),
                $user->getUserIdentifier(),
                $request->label,
                $request->hostUrl,
                $request->path,
                $request->credentials,
            );

            $this->sourceRepository->save($source);
        }

        return $source;
    }
}
