<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SourceRepository;
use App\Request\CreateGitSourceRequest;
use App\Services\SourceFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public const ROUTE_GIT_SOURCE_CREATE = '/git';
    public const ROUTE_SOURCE_GET = '/';

    #[Route(self::ROUTE_GIT_SOURCE_CREATE, name: 'create_git', methods: ['POST'])]
    public function createGitSource(
        UserInterface $user,
        CreateGitSourceRequest $request,
        SourceFactory $sourceFactory
    ): JsonResponse {
        return new JsonResponse($sourceFactory->createGitSourceFromRequest($user, $request));
    }

    #[Route(self::ROUTE_SOURCE_GET . '{sourceId<[A-Z90-9]{26}>}', name: 'get', methods: ['GET'])]
    public function get(string $sourceId, SourceRepository $repository): JsonResponse
    {
        $source = $repository->find($sourceId);
        if (null === $source) {
            return new JsonResponse(null, 404);
        }

        return new JsonResponse($source);
    }
}
