<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Exception\NonUniqueEntityLabelException;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;

class Mutator
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
    ) {
    }

    /**
     * @throws NonUniqueEntityLabelException
     */
    public function updateFile(FileSource $source, FileSourceRequest $request): FileSource
    {
        $existingSource = $this->sourceRepository->findOneBy(
            $this->createFindCriteria($source->getUserId(), $request->label)
        );

        if ($existingSource instanceof GitSource) {
            throw new NonUniqueEntityLabelException('source');
        }

        if ($existingSource instanceof FileSource) {
            if (
                $existingSource->getId() === $source->getId()
                || 0 === $this->sourceRepository->count(['id' => $source->getId()])
            ) {
                return $existingSource;
            }

            throw new NonUniqueEntityLabelException('source');
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
        $existingSource = $this->sourceRepository->findOneBy(
            $this->createFindCriteria($source->getUserId(), $request->label)
        );

        if (
            $existingSource instanceof FileSource
            || ($existingSource instanceof GitSource && $existingSource->getId() !== $source->getId())
        ) {
            throw new NonUniqueEntityLabelException('source');
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
