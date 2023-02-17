<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Exception\EmptyEntityIdException;
use App\Request\GitSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\UsersSecurityBundle\Security\User;

class Factory
{
    public function __construct(
        private readonly Store $store,
        private readonly Finder $finder,
        private readonly EntityIdFactory $entityIdFactory,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     */
    public function createFromGitSourceRequest(User $user, GitSourceRequest $request): GitSource
    {
        $source = new GitSource(
            $this->entityIdFactory->create(),
            $user->getUserIdentifier(),
            $request->label,
            $request->hostUrl,
            $request->path,
            $request->credentials,
        );

        $foundSource = $this->finder->find($source);
        if ($foundSource instanceof GitSource) {
            return $foundSource;
        }

        $this->store->add($source);

        return $source;
    }
}
