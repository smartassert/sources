<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Request\GitSourceRequest;

class SourceMutator
{
    public function __construct(
        private SourcePersister $persister,
    ) {
    }

    public function updateGitSource(GitSource $source, GitSourceRequest $request): GitSource
    {
        $source->setHostUrl($request->getHostUrl());
        $source->setPath($request->getPath());
        $source->setAccessToken($request->getAccessToken());

        $this->persister->persist($source);

        return $source;
    }
}
