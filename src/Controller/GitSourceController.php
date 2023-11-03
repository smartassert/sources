<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GitSource;
use App\Exception\EmptyEntityIdException;
use App\Exception\ModifyReadOnlyEntityException;
use App\Exception\NonUniqueEntityLabelException;
use App\Request\GitSourceRequest;
use App\Services\Source\GitSourceFactory;
use App\Services\Source\Mutator;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/git-source', name: 'git_source_')]
readonly class GitSourceController
{
    public function __construct(
        private GitSourceFactory $gitSourceFactory,
        private Mutator $mutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws NonUniqueEntityLabelException
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(User $user, GitSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->gitSourceFactory->create($user, $request));
    }

    /**
     * @throws ModifyReadOnlyEntityException
     * @throws NonUniqueEntityLabelException
     */
    #[Route(path: '/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN, name: 'update', methods: ['PUT'])]
    public function update(GitSource $source, GitSourceRequest $request): Response
    {
        if (null !== $source->getDeletedAt()) {
            throw new ModifyReadOnlyEntityException($source->getId(), 'source');
        }

        return new JsonResponse($this->mutator->updateGit($source, $request));
    }
}
