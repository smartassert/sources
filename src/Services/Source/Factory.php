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

    public function createFromFileSourceRequest(User $user, FileSourceRequest $request): FileSource
    {
        $source = new FileSource($user->getUserIdentifier(), $request->getLabel());

        $foundSource = $this->finder->find($source);
        if ($foundSource instanceof FileSource) {
            return $foundSource;
        }

        $this->store->add($source);

        return $source;
    }
}
