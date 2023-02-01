<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
use SmartAssert\UsersSecurityBundle\Security\User;

class Factory
{
    public function __construct(
        private Store $store,
        private Finder $finder,
    ) {
    }

    public function createFromSourceRequest(
        User $user,
        SourceRequestInterface $request
    ): ?SourceInterface {
        $source = null;

        if ($request instanceof FileSourceRequest) {
            $source = new FileSource($user->getUserIdentifier(), $request->getLabel());
        }

        if ($request instanceof GitSourceRequest) {
            $source = new GitSource(
                $user->getUserIdentifier(),
                $request->getHostUrl(),
                $request->getPath(),
                $request->getCredentials(),
            );
        }

        if ($source instanceof SourceInterface && null === $this->finder->find($source)) {
            $this->store->add($source);
        }

        return $source;
    }
}
