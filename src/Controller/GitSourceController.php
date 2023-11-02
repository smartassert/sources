<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\EmptyEntityIdException;
use App\Exception\InvalidRequestException;
use App\Exception\NonUniqueEntityLabelException;
use App\Request\GitSourceRequest;
use App\Services\ExceptionFactory;
use App\Services\Source\GitSourceFactory;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/git-source', name: 'git_source_')]
readonly class GitSourceController
{
    public function __construct(
        private ExceptionFactory $exceptionFactory,
        private GitSourceFactory $gitSourceFactory,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws InvalidRequestException
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(User $user, GitSourceRequest $request): JsonResponse
    {
        try {
            return new JsonResponse($this->gitSourceFactory->create($user, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $this->exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'source'
            );
        }
    }
}
