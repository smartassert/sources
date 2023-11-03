<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\EmptyEntityIdException;
use App\Exception\InvalidRequestException;
use App\Exception\ModifyReadOnlyEntityException;
use App\Exception\NonUniqueEntityLabelException;
use App\Request\FileSourceRequest;
use App\Services\InvalidRequestExceptionFactory;
use App\Services\Source\FileSourceFactory;
use App\Services\Source\Mutator;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/file-source', name: 'file_source_')]
readonly class FileSourceController
{
    public function __construct(
        private InvalidRequestExceptionFactory $invalidRequestExceptionFactory,
        private FileSourceFactory $sourceFactory,
        private Mutator $sourceMutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws InvalidRequestException
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(User $user, FileSourceRequest $request): JsonResponse
    {
        try {
            return new JsonResponse($this->sourceFactory->create($user, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $this->invalidRequestExceptionFactory->createFromLabelledObjectRequest($request);
        }
    }

    /**
     * @throws InvalidRequestException
     * @throws ModifyReadOnlyEntityException
     */
    #[Route(path: '/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN, name: 'update', methods: ['PUT'])]
    public function update(FileSource $source, FileSourceRequest $request): Response
    {
        if (null !== $source->getDeletedAt()) {
            throw new ModifyReadOnlyEntityException($source->getId(), 'source');
        }

        try {
            $source = $this->sourceMutator->updateFile($source, $request);
        } catch (NonUniqueEntityLabelException) {
            throw $this->invalidRequestExceptionFactory->createFromLabelledObjectRequest($request);
        }

        return new JsonResponse($source);
    }
}
