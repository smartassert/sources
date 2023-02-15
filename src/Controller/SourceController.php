<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Source\Type;
use App\Exception\InvalidRequestException;
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
    /**
     * @throws InvalidRequestException
     */
    #[Route('/git', name: 'git_source_create', methods: ['POST'])]
    public function createGitSource(
        User $user,
        Factory $factory,
        GitSourceRequest $request
    ): JsonResponse {
        return new JsonResponse($factory->createFromGitSourceRequest($user, $request));
    }

    #[Route('/file', name: 'file_source_create', methods: ['POST'])]
    public function createFileSource(
        User $user,
        Factory $factory,
        FileSourceRequest $request
    ): JsonResponse {
        return new JsonResponse($factory->createFromFileSourceRequest($user, $request));
    }

    #[Route('/list', name: 'source_list', methods: ['GET'])]
    public function list(UserInterface $user, SourceRepository $repository): JsonResponse
    {
        return new JsonResponse($repository->findByUserAndType($user, [Type::FILE, Type::GIT]));
    }
}
