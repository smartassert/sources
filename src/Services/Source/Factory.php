<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Request\FooFileSourceRequest;
use App\Request\FooGitSourceRequest;
use Symfony\Component\Security\Core\User\UserInterface;

class Factory
{
    public function __construct(
        private Store $store,
        private Finder $finder,
    ) {
    }

    public function createFromSourceRequest(
        UserInterface $user,
        FooFileSourceRequest|FooGitSourceRequest $sourceRequest
    ): SourceInterface {
        $source = null;

        if ($sourceRequest instanceof FooFileSourceRequest) {
            $source = new FileSource(
                $user->getUserIdentifier(),
                $sourceRequest->getParameter(FooFileSourceRequest::PARAMETER_LABEL)
            );
        }

        if ($sourceRequest instanceof FooGitSourceRequest) {
            $source = new GitSource(
                $user->getUserIdentifier(),
                $sourceRequest->getParameter(FooGitSourceRequest::PARAMETER_HOST_URL),
                $sourceRequest->getParameter(FooGitSourceRequest::PARAMETER_PATH),
                $sourceRequest->getParameter(FooGitSourceRequest::PARAMETER_CREDENTIALS),
            );
        }

        if ($source instanceof SourceInterface && null === $this->finder->find($source)) {
            $this->store->add($source);
        }

        return $source;
    }
}
