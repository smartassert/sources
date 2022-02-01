<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use Symfony\Component\Security\Core\User\UserInterface;

class Factory
{
    public function __construct(
        private Store $store,
        private Finder $finder,
    ) {
    }

    public function createGitSourceFromRequest(UserInterface $user, GitSourceRequest $request): GitSource
    {
        $source = new GitSource(
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getCredentials()
        );

        if (null === $this->finder->find($source)) {
            $this->store->add($source);
        }

        return $source;
    }

    public function createFileSourceFromRequest(UserInterface $user, FileSourceRequest $request): FileSource
    {
        $source = new FileSource($user->getUserIdentifier(), $request->getLabel());

        if (null === $this->finder->find($source)) {
            $this->store->add($source);
        }

        return $source;
    }
}
