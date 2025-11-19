<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSourceInterface;
use App\Entity\GitSourceInterface;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\ServiceRequest\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Parameter\Parameter;

class Mutator
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly ErrorResponseExceptionFactory $exceptionFactory,
    ) {}

    /**
     * @throws ErrorResponseException
     */
    public function updateFile(FileSourceInterface $source, FileSourceRequest $request): FileSourceInterface
    {
        $existingSource = $this->sourceRepository->findOneBy(
            $this->createFindCriteria($source->getUserId(), $request->label)
        );

        if ($existingSource instanceof GitSourceInterface) {
            throw $this->exceptionFactory->createForDuplicateObject(new Parameter('label', $request->getLabel()));
        }

        if ($existingSource instanceof FileSourceInterface) {
            if (
                $existingSource->getId() === $source->getId()
                || 0 === $this->sourceRepository->count(['id' => $source->getId()])
            ) {
                return $existingSource;
            }

            throw $this->exceptionFactory->createForDuplicateObject(new Parameter('label', $request->getLabel()));
        }

        $source->setLabel($request->label);
        $this->sourceRepository->save($source);

        return $source;
    }

    /**
     * @throws ErrorResponseException
     */
    public function updateGit(GitSourceInterface $source, GitSourceRequest $request): GitSourceInterface
    {
        $existingSource = $this->sourceRepository->findOneBy(
            $this->createFindCriteria($source->getUserId(), $request->label)
        );

        if (
            $existingSource instanceof FileSourceInterface
            || ($existingSource instanceof GitSourceInterface && $existingSource->getId() !== $source->getId())
        ) {
            throw $this->exceptionFactory->createForDuplicateObject(new Parameter('label', $request->getLabel()));
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
