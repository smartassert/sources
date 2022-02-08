<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
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
        SourceRequestInterface $request
    ): ?SourceInterface {
        $source = null;

        if ($request instanceof FileSourceRequest) {
            $source = new FileSource(
                $user->getUserIdentifier(),
                $request->getParameter(FileSourceRequest::PARAMETER_LABEL)
            );
        }

        if ($request instanceof GitSourceRequest) {
            $source = new GitSource(
                $user->getUserIdentifier(),
                $request->getParameter(GitSourceRequest::PARAMETER_HOST_URL),
                $request->getParameter(GitSourceRequest::PARAMETER_PATH),
                $request->getParameter(GitSourceRequest::PARAMETER_CREDENTIALS),
            );
        }

        if ($source instanceof SourceInterface && null === $this->finder->find($source)) {
            $this->store->add($source);
        }

        return $source;
    }
}
