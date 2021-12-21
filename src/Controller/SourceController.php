<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GitSource;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Services\SourceFactory;
use App\Services\SourceMutator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public const ROUTE_GIT_SOURCE_CREATE = '/git';
    public const ROUTE_SOURCE = '/';

    #[Route(self::ROUTE_GIT_SOURCE_CREATE, name: 'create_git', methods: ['POST'])]
    public function createGitSource(
        UserInterface $user,
        GitSourceRequest $request,
        SourceFactory $sourceFactory
    ): JsonResponse {
        return new JsonResponse($sourceFactory->createGitSourceFromRequest($user, $request));
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'get', methods: ['GET'])]
    public function get(string $sourceId, SourceRepository $repository, UserInterface $user): JsonResponse
    {
        $source = $repository->find($sourceId);
        if (null === $source) {
            return new JsonResponse(null, 404);
        }

        if ($user->getUserIdentifier() !== $source->getUserId()) {
            return new JsonResponse(null, 401);
        }

        return new JsonResponse($source);
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'update', methods: ['PUT'])]
    public function update(
        string $sourceId,
        Request $request,
        SourceRepository $repository,
        SourceMutator $sourceMutator,
        UserInterface $user,
    ): JsonResponse {
        $source = $repository->find($sourceId);
        if (null === $source) {
            return new JsonResponse(null, 404);
        }

        if ($user->getUserIdentifier() !== $source->getUserId()) {
            return new JsonResponse(null, 401);
        }

        if (!$source instanceof GitSource) {
            return new JsonResponse(null, 404);
        }

        $source = $sourceMutator->updateGitSource($source, GitSourceRequest::create($request));

        return new JsonResponse($source);
    }
}
