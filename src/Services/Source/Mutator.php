<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;

class Mutator
{
    public function __construct(
        private Store $store,
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

    public function updateFileSource(FileSource $source, FileSourceRequest $request): FileSource
    {
        $source->setLabel($request->getLabel());

        $this->store->add($source);

        return $source;
    }
}
