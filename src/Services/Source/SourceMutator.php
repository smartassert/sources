<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Request\GitSourceRequest;

class SourceMutator
{
    public function __construct(
        private SourceStore $store,
    ) {
    }

    public function updateGitSource(GitSource $source, GitSourceRequest $request): GitSource
    {
        $source->setHostUrl($request->getHostUrl());
        $source->setPath($request->getPath());
        $source->setAccessToken($request->getAccessToken());

        $this->store->add($source);

        return $source;
    }
}
