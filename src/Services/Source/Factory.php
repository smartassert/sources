<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
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

    /**
     * @param array<string, string> $parameters
     */
    public function createRunSource(FileSource|GitSource $parent, array $parameters = []): RunSource
    {
        ksort($parameters);

        return new RunSource($parent, $parameters);
    }

    public function createGitSourceFromRequest(UserInterface $user, GitSourceRequest $request): GitSource
    {
        $source = new GitSource(
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->hasCredentials() ? $request->getCredentials() : null
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
