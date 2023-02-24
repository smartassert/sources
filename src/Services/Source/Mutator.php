<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Exception\NonUniqueEntityLabelException;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;

class Mutator
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly FileSourceRepository $fileSourceRepository,
        private readonly GitSourceRepository $gitSourceRepository,
    ) {
    }

    /**
     * @throws NonUniqueEntityLabelException
     */
    public function updateFile(FileSource $source, FileSourceRequest $request): FileSource
    {
        $foundSource = $this->fileSourceRepository->findOneBy(
            $this->createFindCriteria($source->getUserId(), $request->label)
        );

        if ($foundSource instanceof FileSource) {
            if (
                $foundSource->getId() === $source->getId()
                || 0 === $this->sourceRepository->count(['id' => $source->getId()])
            ) {
                return $foundSource;
            }

            throw new NonUniqueEntityLabelException();
        }

        $source->setLabel($request->label);
        $this->sourceRepository->save($source);

        return $source;
    }

    /**
     * @throws NonUniqueEntityLabelException
     */
    public function updateGit(GitSource $source, GitSourceRequest $request): GitSource
    {
        $foundSource = $this->gitSourceRepository->findOneBy(
            $this->createFindCriteria($source->getUserId(), $request->label)
        );

        if ($foundSource instanceof GitSource) {
            if ($foundSource->getId() === $source->getId()) {
                return $foundSource;
            }

            throw new NonUniqueEntityLabelException();
        }

        $source->setLabel($request->label);
        $source->setHostUrl($request->hostUrl);
        $source->setPath($request->path);
        $source->setCredentials($request->credentials);

        $this->sourceRepository->save($source);

        return $source;
    }

    /**
     * @return array{userId: string, label: string, deletedAt: null}
     */
    private function createFindCriteria(string $userId, string $label): array
    {
        return [
            'userId' => $userId,
            'label' => $label,
            'deletedAt' => null,
        ];
    }
}
