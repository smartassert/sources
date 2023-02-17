<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;

class Mutator
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
    ) {
    }

    public function updateFile(FileSource $source, FileSourceRequest $request): SourceInterface
    {
        $source->setLabel($request->label);
        $this->sourceRepository->save($source);

        return $source;
    }

    public function updateGit(GitSource $source, GitSourceRequest $request): SourceInterface
    {
        $source->setLabel($request->label);
        $source->setHostUrl($request->hostUrl);
        $source->setPath($request->path);
        $source->setCredentials($request->credentials);

        $this->sourceRepository->save($source);

        return $source;
    }
}
