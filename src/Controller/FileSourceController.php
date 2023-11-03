<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\EmptyEntityIdException;
use App\Exception\ModifyReadOnlyEntityException;
use App\Exception\NonUniqueEntityLabelException;
use App\Request\FileSourceRequest;
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
        private FileSourceFactory $sourceFactory,
        private Mutator $sourceMutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws NonUniqueEntityLabelException
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(User $user, FileSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->sourceFactory->create($user, $request));
    }

    /**
     * @throws ModifyReadOnlyEntityException
     * @throws NonUniqueEntityLabelException
     */
    #[Route(path: '/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN, name: 'update', methods: ['PUT'])]
    public function update(FileSource $source, FileSourceRequest $request): Response
    {
        if (null !== $source->getDeletedAt()) {
            throw new ModifyReadOnlyEntityException($source->getId(), 'source');
        }

        return new JsonResponse($this->sourceMutator->updateFile($source, $request));
    }
}
