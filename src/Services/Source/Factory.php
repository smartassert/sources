<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use SmartAssert\UsersSecurityBundle\Security\User;

class Factory
{
    public function __construct(
        private readonly Store $store,
        private readonly Finder $finder,
    ) {
    }

    public function createFromGitSourceRequest(User $user, GitSourceRequest $request): GitSource
    {
        $source = new GitSource(
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getCredentials(),
        );

        if (null === $this->finder->find($source)) {
            $this->store->add($source);
        }

        return $source;
    }

    public function createFromFileSourceRequest(User $user, FileSourceRequest $request): FileSource
    {
        $source = new FileSource($user->getUserIdentifier(), $request->getLabel());

        if (null === $this->finder->find($source)) {
            $this->store->add($source);
        }

        return $source;
    }
}
