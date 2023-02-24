<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Exception\NonUniqueSourceLabelException;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;

class Mutator
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly FileSourceFinder $fileSourceFinder,
        private readonly GitSourceFinder $gitSourceFinder,
    ) {
    }

    /**
     * @throws NonUniqueSourceLabelException
     */
    public function updateFile(FileSource $source, FileSourceRequest $request): FileSource
    {
        $foundSource = $this->fileSourceFinder->find($source->getUserId(), $request->label);
        if ($foundSource instanceof FileSource) {
            if (
                $foundSource->getId() === $source->getId()
                || 0 === $this->sourceRepository->count(['id' => $source->getId()])
            ) {
                return $foundSource;
            }

            throw new NonUniqueSourceLabelException();
        }

        $source->setLabel($request->label);
        $this->sourceRepository->save($source);

        return $source;
    }

    /**
     * @throws NonUniqueSourceLabelException
     */
    public function updateGit(GitSource $source, GitSourceRequest $request): GitSource
    {
        $foundSource = $this->gitSourceFinder->find($source->getUserId(), $request->label);
        if ($foundSource instanceof GitSource) {
            if ($foundSource->getId() === $source->getId()) {
                return $foundSource;
            }

            throw new NonUniqueSourceLabelException();
        }

        $source->setLabel($request->label);
        $source->setHostUrl($request->hostUrl);
        $source->setPath($request->path);
        $source->setCredentials($request->credentials);

        $this->sourceRepository->save($source);

        return $source;
    }
}
