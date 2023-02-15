<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Factory;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public function __construct(
        private readonly Factory $factory,
        private readonly SourceRepository $repository,
    ) {
    }

    #[Route('/git', name: 'git_source_create', methods: ['POST'])]
    public function createGitSource(User $user, GitSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->factory->createFromGitSourceRequest($user, $request));
    }

    #[Route('/file', name: 'file_source_create', methods: ['POST'])]
    public function createFileSource(User $user, FileSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->factory->createFromFileSourceRequest($user, $request));
    }

    #[Route('/list', name: 'source_list', methods: ['GET'])]
    public function list(UserInterface $user): JsonResponse
    {
        return new JsonResponse($this->repository->findByUserAndType($user, [Type::FILE, Type::GIT]));
    }
}
